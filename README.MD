# SolidBunch Composer Installers

### [Website](https://starter-kit.io) | [Documentation](https://starter-kit.io/docs/overview/)

## Overview

Custom Composer installers for **StarterKit** projects by SolidBunch.

Supports custom types:

* `kit-module` — for reusable modules located in `kit-modules/`
* `wordpress-core` — for downloading core WordPress files

## Installation

In your project `composer.json`:

```json
"require": {
  "solidbunch/composer-installers": "^1.1"
},
"config": {
  "allow-plugins": {
    "solidbunch/composer-installers": true
  }
},
"extra": {
  "installer-paths": {
    "kit-modules/{$name}/": ["type:kit-module"],
    "web/wp-core/": ["type:wordpress-core"]
  }
}
```

Composer 2.2+ only runs plugins listed in `allow-plugins`, so the `config` block above is required.

## Behavior

* If `installer-paths` are defined — packages will be installed accordingly.
* If not — packages will default to `vendor/`.
* `{$vendor}` and `{$name}` in an install path are replaced with the package's vendor and name, e.g. `kit-modules/{$vendor}/{$name}/`.
* The installer claims a supported package type only when `extra.installer-paths` has a rule that can match it: a `type:` rule naming that exact type, or any rule that is not a `type:` rule. Otherwise another installer (or Composer's default) handles the package.
* A package-name rule makes the installer claim the whole type, because a name cannot be checked when the type is claimed. Another installer for that type is then not consulted, and packages of that type without a matching rule go to `vendor/`.
* Rules naming an exact package (`"vendor/name"`) are checked before `type:` rules; inside each group the first rule in `installer-paths` order wins.
* Package-name rules only apply to packages of the supported types `kit-module` and `wordpress-core`; a package of any other type is never handled by this installer.
* A trailing slash in an `installer-paths` key is accepted and stripped from the resulting install path, because Composer's installer contract forbids a trailing slash.
* The `vendor:` prefix known from `composer/installers` is not supported.
* `type:*` is not a wildcard; a `type:` rule must name the exact type.

## Stay Connected

- Participate on [GitHub Discussions](https://github.com/solidbunch/starter-kit-foundation/discussions)
- Connect via [LinkedIn](https://www.linkedin.com/company/solidbunch)
