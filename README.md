# Spiggle Rules Engine

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11+-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-5-FFAA00)](https://filamentphp.com)

**Tenant-scoped lifecycle rules for Laravel Filament — fire actions when members hit age milestones, tenure, inactivity, or spend thresholds.**

| | |
|---|---|
| **Package** | `spiggle/rules-engine` v1.0.0 |
| **License** | MIT (free & open source) |
| **GitHub** | [skillbobby/Spiggle-Rules-Engine](https://github.com/skillbobby/Spiggle-Rules-Engine) |
| **Docs** | [Product site & guide](https://skillbobby.github.io/Spiggle-Rules-Engine/) |

---

## Why use Rules Engine?

- **Zero-code automation** — build lifecycle rules in Filament, no custom artisan commands per trigger
- **Preset triggers** — age milestones, tenure, inactivity, metric thresholds, and custom field matching out of the box
- **Idempotent execution** — rules fire at most once per subject per rule, tracked in an audit log
- **Visual conditions** — AND conditions filter subjects after the preset matches, configurable in the UI
- **Tenant-scoped** — each scope (organisation / family / team) manages its own rule set
- **Host-wired actions** — notify admins, create reminders, and dispatch events through your own callbacks

---

## Features

| Feature | Description |
|---|---|
| **Preset triggers** | 5 built-in trigger types cover the most common lifecycle events |
| **Visual condition builder** | AND conditions on attribute / operator / value pairs |
| **5 built-in actions** | Meta updates, settings merge, notifications, reminders, events |
| **Idempotency** | `rule_execution_logs` prevents duplicate fires |
| **Filament UI** | Full CRUD for rules inside your existing panel |
| **Login evaluation** | Optionally evaluate rules on member login |
| **Scheduled evaluation** | `RuleEvaluator::evaluateAllActiveRules()` for cron runs |
| **Audit trail** | Every execution logged with subject, rule, trigger, and outcome |

---

## Requirements

- PHP **8.2+**
- Laravel **11+**
- Filament **^5**

---

## Installation

### 1. Require the package

```bash
composer require spiggle/rules-engine
```

### 2. Publish config & migrations, then migrate

```bash
php artisan vendor:publish --tag=rules-engine-config
php artisan vendor:publish --tag=rules-engine-migrations
php artisan migrate
```

### 3. Register the Filament plugin

In your panel provider (e.g. `app/Providers/Filament/AdminPanelProvider.php`):

```php
use Spiggle\RulesEngine\Filament\RulesEnginePlugin;

$panel->plugin(RulesEnginePlugin::make());
```

---

## Quick start

1. Sign in to your Filament panel.
2. Navigate to **Rules Engine → Rules**.
3. Create a rule, choose a preset trigger and configure its parameters.
4. Add optional AND conditions to narrow the subject set.
5. Add one or more actions to fire when the rule matches.
6. Set the rule to **Active**.

The engine evaluates all active rules whenever you call:

```php
app(\Spiggle\RulesEngine\Services\RuleEvaluator::class)
    ->evaluateAllActiveRules('scheduled');
```

Wire this into your Laravel scheduler:

```php
Schedule::call(function () {
    app(\Spiggle\RulesEngine\Services\RuleEvaluator::class)
        ->evaluateAllActiveRules('scheduled');
})->dailyAt('01:15');
```

---

## Preset triggers

| Preset | Key | Parameters |
|---|---|---|
| Age milestone | `age_milestone` | `dob_attribute`, `age` (years) |
| Tenure milestone | `tenure_milestone` | `joined_date_attribute`, `days` |
| Inactivity | `inactivity` | `last_active_attribute`, `days` |
| Metric threshold | `metric_threshold` | `metric_key`, `operator`, `value` |
| Custom field match | `custom_field_match` | `attribute`, `operator`, `value` |

`metric_threshold` requires a host-provided binding for `MetricProviderInterface`.

---

## Actions

| Action | Key | Description |
|---|---|---|
| Set member meta | `set_member_meta` | Merges key/value into the subject pivot JSON meta |
| Set scope settings | `set_scope_settings` | Merges key/value into tenant-level settings |
| Notify scope admins | `notify_scope_admins` | Calls your `notify_scope_admins` host callback |
| Create reminder | `create_reminder` | Calls your `create_reminder` host callback |
| Dispatch event | `dispatch_event` | Fires a named Laravel event with subject payload |

---

## Host wiring

Configure `config/rules-engine.php` to connect the engine to your application's models and callbacks:

```php
return [
    // The Eloquent model that represents a "subject" (e.g. a member/user)
    'subject_model' => \App\Models\Member::class,

    // The Eloquent model that represents a "scope" (e.g. a family/organisation)
    'scope_model' => \App\Models\Family::class,

    // Resolves the current subject from a context array
    'subject_resolver' => function (array $context): ?\App\Models\Member {
        return \App\Models\Member::find($context['subject_id'] ?? null);
    },

    // Resolves the current scope from a context array
    'scope_resolver' => function (array $context): ?\App\Models\Family {
        return \App\Models\Family::find($context['scope_id'] ?? null);
    },

    // Gates who can manage rules in the Filament UI
    'can_manage_rules' => function (\App\Models\User $user): bool {
        return $user->hasRole('admin');
    },

    // Called by the notify_scope_admins action
    'notify_scope_admins' => function ($scope, array $payload): void {
        // send your notification here
    },

    // Called by the create_reminder action
    'create_reminder' => function ($subject, $scope, array $payload): void {
        // create your reminder here
    },

    // Optional: bind a MetricProviderInterface implementation for metric_threshold presets
    'metric_provider' => null,
];
```

---

## Related Spiggle packages

| Package | Description |
|---|---|
| [Spiggle Form Builder Core](https://github.com/skillbobby/Spiggle-Form-Builder-Core) | Public forms for Laravel + Filament |
| [Spiggle Dynamic Fields Core](https://github.com/skillbobby/Spiggle-Dynamic-Fields-Core) | Reusable custom field types for Eloquent models |
| [Spiggle Visual Dashboards](https://github.com/skillbobby/Spiggle-Visual-Dashboards) | Power BI-style dashboard canvas for Laravel Filament |
| [Spiggle Theme](https://github.com/skillbobby/filament-spiggle-theme) | Crisp, clean, high-energy Filament theme |

---

## Sponsors

If this package is useful, consider supporting development:

[![GitHub Sponsors](https://img.shields.io/badge/sponsor-30363D?style=for-the-badge&logo=GitHub-Sponsors&logoColor=%23EA4AAA)](https://github.com/sponsors/skillbobby)

## License

MIT — free and open source forever. See [LICENSE](LICENSE).
