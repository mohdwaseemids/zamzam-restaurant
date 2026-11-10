<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;
use Livewire\WithPagination;

/** Customer list built from order history (grouped by mobile number). */
class Customers extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.customers';

    public string $search = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('view customers');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function getViewData(): array
    {
        $term = trim($this->search);

        $customers = Order::query()
            ->where('phone', '!=', '')
            ->where('status', '!=', 'cancelled')
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('phone', 'like', "%{$term}%")->orWhere('customer_name', 'like', "%{$term}%")))
            ->selectRaw('phone, MAX(customer_name) as name, COUNT(*) as orders_count, SUM(total) as spent, MAX(created_at) as last_order_at')
            ->groupBy('phone')
            ->orderByDesc('spent')
            ->paginate(20);

        return [
            'customers' => $customers,
            'totalCustomers' => Order::where('phone', '!=', '')->where('status', '!=', 'cancelled')->distinct()->count('phone'),
        ];
    }
}
