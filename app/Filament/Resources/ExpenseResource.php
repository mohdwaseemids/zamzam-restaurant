<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageExpenses;
use App\Models\Expense;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'expenses';

    protected static ?string $model = Expense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('spent_on')->label('Date')->default(now())->required()->native(false)->maxDate(now()),
            TextInput::make('amount')->numeric()->prefix('₹')->required()->minValue(0.01),
            Select::make('category')->options(Expense::CATEGORIES)->required()->native(false),
            Select::make('paid_via')->label('Paid by')->options(Expense::PAID_VIA)->default('cash')->required()->native(false),
            TextInput::make('description')->maxLength(255)->columnSpanFull()->placeholder('20 kg mutton from Qureshi Meat'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('spent_on')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('category')->badge()->color('gray')->formatStateUsing(fn (string $state): string => Expense::CATEGORIES[$state] ?? $state)
                    ->description(fn (Expense $record): ?string => $record->description),
                TextColumn::make('paid_via')->label('Paid by')->formatStateUsing(fn (string $state): string => Expense::PAID_VIA[$state] ?? $state)->visibleFrom('md'),
                TextColumn::make('user.name')->label('Added by')->placeholder('—')->visibleFrom('lg'),
                TextColumn::make('amount')->money('INR')->sortable()->weight('bold')->summarize(Sum::make()->money('INR')->label('Total')),
            ])
            ->filters([
                SelectFilter::make('category')->options(Expense::CATEGORIES),
                Filter::make('this_month')->label('This month')->default()
                    ->query(fn (Builder $query): Builder => $query->where('spent_on', '>=', now()->startOfMonth())),
            ])
            ->defaultSort('spent_on', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageExpenses::route('/')];
    }
}
