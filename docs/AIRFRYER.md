# Xiaomi Air Fryer (PHP only)

Підтримка аерогрилів через miIO/MIoT без Python.

## Моделі
`careli.fryer.maf01/02/03/05a/06/07/10a/14`, `xiaomi.fryer.maf14`, `viomi.fryer.v3`, `silen.fryer.sck501/505`, `534`.

## Підключення
1. Отримайте IP і токен (32 hex) з резервної копії Mi Home.
2. Додайте пристрій у `Xiaomi miIO -> Devices` з типом `careli.fryer.*`.
3. Відкрийте кнопку **Air Fryer**.

## Можливості
- **Status** — `get_properties`: статус, час, температура, рецепти, вага їжі, попередній нагрів.
- **Recipes** — читання `recipe_sync`, редагування JSON, **Save recipe** (`set_properties`), **Start recipe** (`start_recipe_cook`).
- Власний режим: `target_time` (0..1440), `target_temperature` (40..230), `food_quanty` (0..5).
- Керування: **Start**, **Pause**, **Resume**, **Stop**.

## Діагностика
- `Device not found` — перевірте ID.
- `Unknown property` — властивість відсутня у мапі моделі.
- `Invalid JSON recipe` — виправте JSON перед збереженням.
- Порожній `recipe_sync` — синхронізуйте рецепти в Mi Home.

## Сценарій
```php
$m = getModule('xiaomimiio');
$m->processCommand($device_id, 'target_temperature', '180');
$m->processCommand($device_id, 'target_time', '15');
$m->processCommand($device_id, 'app_start', '');
```