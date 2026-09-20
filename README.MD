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
"extra": {
  "installer-paths": {
    "kit-modules/{$name}/": ["type:kit-module"],
    "web/wp-core/": ["type:wordpress-core"]
  }
}
```

## Behavior

* If `installer-paths` are defined — packages will be installed accordingly.
* If not — packages will default to `vendor/`.
* The installer claims a package type only when `extra.installer-paths` has a rule that can match it; otherwise another installer (or Composer's default) handles the package.

## Stay Connected

- Participate on [GitHub Discussions](https://github.com/solidbunch/starter-kit-foundation/discussions)
- Connect via [LinkedIn](https://www.linkedin.com/company/solidbunch)
