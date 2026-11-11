<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Sales report';

    protected static ?string $title = 'Sales report';

    protected string $view = 'filament.pages.sales-report';

    public string $from = '';

    public string $to = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('view reports');
    }

    public function mount(): void
    {
        $this->setRange('month');
    }

    public function setRange(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'today' => [today(), today()],
            'yesterday' => [today()->subDay(), today()->subDay()],
            'week' => [today()->subDays(6), today()],
            'last_month' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            default => [today()->startOfMonth(), today()],
        };

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function range(): array
    {
        try {
            $from = Carbon::parse($this->from)->startOfDay();
            $to = Carbon::parse($this->to)->endOfDay();
        } catch (\Throwable) {
            $from = today()->startOfMonth();
            $to = today()->endOfDay();
        }

        return $from->gt($to) ? [$to->copy()->startOfDay(), $from->copy()->endOfDay()] : [$from, $to];
    }

    protected function orders(): Builder
    {
        [$from, $to] = $this->range();

        return Order::query()->whereBetween('created_at', [$from, $to]);
    }

    protected function getViewData(): array
    {
        $valid = fn () => $this->orders()->where('status', '!=', 'cancelled');

        $count = $valid()->count();
        $revenue = (float) $valid()->sum('total');

        [$from, $to] = $this->range();
        $expenses = 0.0;

        return [
            'stats' => [
                'Orders' => number_format($count),
                'Sales' => money($revenue),
                'Average order' => money($count ? $revenue / $count : 0),
                'Collected' => money((float) $valid()->where('payment_status', 'paid')->sum('total')),
                'Discounts given' => money((float) $valid()->sum('discount')),
                'Cancelled orders' => number_format($this->orders()->where('status', 'cancelled')->count()),
                'Expenses' => money($expenses = (float) Expense::whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])->sum('amount')),
                'Sales minus expenses' => money($revenue - $expenses),
            ],
            'expensesByCategory' => Expense::whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('category, SUM(amount) as total')->groupBy('category')->orderByDesc('total')->get()
                ->map(fn ($row): array => ['label' => Expense::CATEGORIES[$row->category] ?? $row->category, 'total' => $row->total]),
            'byType' => $valid()->selectRaw('type, COUNT(*) as orders_count, SUM(total) as revenue')->groupBy('type')->get()
                ->map(fn ($row) => ['label' => Order::TYPES[$row->type] ?? $row->type, 'orders' => $row->orders_count, 'revenue' => $row->revenue]),
            'byPayment' => $valid()->selectRaw('payment_method, COUNT(*) as orders_count, SUM(total) as revenue')->groupBy('payment_method')->get()
                ->map(fn ($row) => ['label' => Order::PAYMENT_METHODS[$row->payment_method] ?? $row->payment_method, 'orders' => $row->orders_count, 'revenue' => $row->revenue]),
            'daily' => $valid()->selectRaw('DATE(created_at) as day, COUNT(*) as orders_count, SUM(total) as revenue')->groupBy('day')->orderByDesc('day')->limit(62)->get(),
            'dishes' => OrderItem::query()
                ->whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled')->whereBetween('created_at', [$from, $to]))
                ->selectRaw('name, SUM(quantity) as qty, SUM(total) as revenue')
                ->groupBy('name')->orderByDesc('revenue')->limit(15)->get(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('Download CSV')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn (): StreamedResponse => $this->export()),
        ];
    }

    public function export(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        $orders = $this->orders()->with('items')->orderBy('created_at')->get();
        $filename = 'zamzam-orders-'.$this->from.'-to-'.$this->to.'.csv';

        // Stops spreadsheet apps from running customer-typed text as a formula.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return response()->streamDownload(function () use ($orders, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 mark so Excel shows ₹ and Hindi text correctly
            fputcsv($out, ['Order', 'Date', 'Customer', 'Phone', 'Type', 'Items', 'Subtotal', 'Discount', 'Delivery', 'Tax', 'Total', 'Payment method', 'Payment status', 'Order status', 'Source']);

            foreach ($orders as $order) {
                fputcsv($out, array_map($safe, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->customer_name,
                    $order->phone,
                    $order->type_label,
                    $order->items->map(fn ($item) => $item->name.' x'.$item->quantity)->implode('; '),
                    $order->subtotal, $order->discount, $order->delivery_charge, $order->tax, $order->total,
                    Order::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method,
                    $order->payment_status,
                    $order->status_label,
                    $order->source,
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
