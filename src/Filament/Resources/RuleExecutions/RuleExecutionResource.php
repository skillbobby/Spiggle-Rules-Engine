<?php

namespace Spiggle\RulesEngine\Filament\Resources\RuleExecutions;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Spiggle\RulesEngine\Enums\ExecutionStatus;
use Spiggle\RulesEngine\Filament\Resources\RuleExecutions\Pages\ListRuleExecutions;
use Spiggle\RulesEngine\Models\RuleExecution;
use Spiggle\RulesEngine\Services\RuleEvaluator;
use Spiggle\RulesEngine\Support\AuthorizesRulesEngine;
use UnitEnum;

class RuleExecutionResource extends Resource
{
    protected static ?string $model = RuleExecution::class;

    protected static ?string $navigationLabel = 'Execution log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('rules-engine.navigation_group', 'Insights');
    }

    public static function getModelLabel(): string
    {
        return 'Execution';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Execution log';
    }

    public static function canAccess(): bool
    {
        return AuthorizesRulesEngine::canManage();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('triggerRule.name')->label('Rule')->searchable(),
                TextColumn::make('subject_id')->label('Subject'),
                TextColumn::make('status')->badge(),
                TextColumn::make('executed_at')->dateTime()->sortable(),
                TextColumn::make('error')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('executed_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(ExecutionStatus::class),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Details')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading('Execution details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (RuleExecution $record) => view('rules-engine::execution-detail', [
                        'record' => $record,
                    ])),
                Action::make('retry')
                    ->label('Retry')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (RuleExecution $record): bool => $record->status === ExecutionStatus::Failed)
                    ->action(function (RuleExecution $record, RuleEvaluator $evaluator): void {
                        $evaluator->retryExecution($record);
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('retryFailed')
                    ->label('Retry failed')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (Collection $records, RuleEvaluator $evaluator): void {
                        $records
                            ->filter(fn (RuleExecution $r): bool => $r->status === ExecutionStatus::Failed)
                            ->each(fn (RuleExecution $r) => $evaluator->retryExecution($r));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRuleExecutions::route('/'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        $tenant = filament()->getTenant();

        if (! $tenant) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('triggerRule', function ($q) use ($tenant): void {
            $q->where('scope_type', $tenant->getMorphClass())
                ->where('scope_id', $tenant->getKey());
        });
    }
}
