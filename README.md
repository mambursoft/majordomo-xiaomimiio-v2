# Xiaomi miIO v2

Experimental v2 package prepared by mambursoft. Published separately from the original module because the current upstream implementation needs architectural changes.

## Status

- separate package, not a drop-in update for the original module;
- install instead of the original module, not alongside it;
- upstream proposal: https://github.com/sergejey/majordomo/issues/1362

## Architecture

1. Presentation layer: templates and CSS tokens, responsive layouts, 44 px touch targets.
2. Application layer: module class coordinates search/edit screens and delegates to services.
3. Domain layer: device/topic services, config validation, queue handling.
4. Infrastructure layer: transport clients (HTTP/MQTT/UDP) and persistence.

No external processes are used for cryptographic or cloud operations.

## Installation

Download the tar.gz archive from the repository releases or use the marketplace package URL. Copy the contents into the MajorDoMo root, then open Control Panel and enable the module.

## Interface manual

Open Control Panel -> Xiaomi miIO v2 -> Manual. The same manual is available at `docs/MANUAL.md` in the package.

## Localization

Languages: English (default/en), Russian (ru), Ukrainian (ua/uk).

## Integration

Use standard MajorDoMo calls, for example:

`
callAPI('/api/module/xiaomimiio', 'GET', array('op' => 'status'));
`

See `docs/MANUAL.md` for module-specific examples.

## Verification

- PHP 7.3+ syntax checks;
- light/dark theme and 375/1440 px layouts checked in Docker;
- no CDN requests, no shell executors for cloud/crypto operations.