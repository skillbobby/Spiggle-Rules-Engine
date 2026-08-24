<?php

namespace Spiggle\RulesEngine\Filament\Resources\TriggerRules;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spiggle\RulesEngine\Enums\ActionType;
use Spiggle\RulesEngine\Enums\RuleFrequency;
use Spiggle\RulesEngine\Enums\RulePreset;
use Spiggle\RulesEngine\Filament\Resources\TriggerRules\Pages\ListTriggerRules;
use Spiggle\RulesEngine\Models\TriggerRule;
use Spiggle\RulesEngine\Services\RuleEvaluator;
use Spiggle\RulesEngine\Support\AuthorizesRulesEngine;
use UnitEnum;

class TriggerRuleResource extends Resource
{
    protected static ?string $model = TriggerRule::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('rules-engine.navigation_group', 'Insights');
    }

    public static function getNavigationLabel(): string
    {
        return 'Trigger rules';
    }

    public static function getModelLabel(): string
    {
        return 'Trigger rule';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Trigger rules';
    }

    public static function canAccess(): bool
    {
        return AuthorizesRulesEngine::canManage();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Trigger')
                ->schema([
                    TextInput::make('name')->required()->maxLength(120),
                    Select::make('preset_key')
                        ->label('Preset')
                        ->options(RulePreset::class)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, callable $set): void {
                            if (! $state) {
                                return;
                            }

                            $preset = RulePreset::tryFrom($state);

                            if ($preset) {
                                $set('trigger_config', $preset->configFields());
                            }
                        }),
                    KeyValue::make('trigger_config')
                        ->label('Trigger config')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Conditions')
                ->description('Optional AND filters applied after the preset matches.')
                ->schema([
                    Repeater::make('conditions')
                        ->schema([
                            TextInput::make('attribute')
                                ->required()
                                ->datalist(['date_of_birth', 'last_login_at', 'email', 'name'])
                                ->helperText('Subject attribute or mapped field.'),
                            Select::make('operator')
                                ->options([
                                    'equals' => 'Equals',
                                    'not_equals' => 'Not equals',
                                    'contains' => 'Contains',
                                    'gt' => 'Greater than',
                                    'gte' => 'Greater or equal',
                                    'lt' => 'Less than',
                                    'lte' => 'Less or equal',
                                    'is_set' => 'Is set',
                                    'is_empty' => 'Is empty',
                                ])
                                ->default('equals')
                                ->required(),
                            TextInput::make('value')
                                ->helperText('Ignored for is_set / is_empty.'),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->collapsible()
                        ->columnSpanFull(),
                ]),
            Section::make('Actions')
                ->schema([
                    Repeater::make('actions')
                        ->schema([
                            Select::make('type')
                                ->options(ActionType::class)
                                ->required(),
                            KeyValue::make('config')
                                ->keyLabel('Key')
                                ->valueLabel('Value'),
                        ])
                        ->columnSpanFull()
                        ->defaultItems(1),
                ]),
            Section::make('Scope & schedule')
                ->schema([
                    KeyValue::make('applies_to')
                        ->label('Applies to (roles, user_ids)')
                        ->helperText('Example: roles = child,parent or user_ids = 1,2')
                        ->columnSpanFull(),
                    Select::make('frequency')
                        ->options(RuleFrequency::class)
                        ->default(RuleFrequency::OnMaintain)
                        ->required(),
                    TextInput::make('priority')
                        ->numeric()
                        ->default(100)
                        ->required(),
                    Toggle::make('is_active')->default(true),
                    Textarea::make('notes')->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('preset_key')->badge()->label('Preset'),
                TextColumn::make('frequency')->badge(),
                TextColumn::make('priority')->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('priority')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => AuthorizesRulesEngine::assignScope($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data): array => AuthorizesRulesEngine::assignScope($data)),
                Action::make('dryRun')
                    ->label('Dry run')
                    ->icon(Heroicon::OutlinedBeaker)
                    ->modalHeading('Dry run preview')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (TriggerRule $record) {
                        $result = app(RuleEvaluator::class)->evaluate(
                            $record->scope,
                            null,
                            true,
                            'dry_run',
                            false,
                            $record,
                        );

                        return view('rules-engine::dry-run-preview', ['result' => $result]);
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTriggerRules::route('/'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return AuthorizesRulesEngine::tenantScopeQuery(parent::getEloquentQuery());
    }
}
