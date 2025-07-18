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
  "solidbunch/composer-installers": "dev-main"
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

## Stay Connected

- Participate on [GitHub Discussions](https://github.com/solidbunch/starter-kit-foundation/discussions)
- Connect via [LinkedIn](https://www.linkedin.com/company/solidbunch)
