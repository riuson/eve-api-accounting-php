## Содержание ##

Настройка автоматического обновления данных


## Подробности ##

Интервалы обновления данных выбраны такие:
| **интервал** | **файл скрипта** | **обновляемая информация** |
|:---------------------|:----------------------------|:------------------------------------------------|
| 1 сутки | update\_common.php | общая информация eve (альянсы, ошибки api, топ фракционных войн, аутопосты, типы переводов, суверенитеты) |
| 1 сутки | update\_assets.php | перечень имущества корпорации|
| 1 час | update\_jobs | список установленных в производстве работ |
| 2 часа | update\_kills.php | kill mails |
| 2 часа | update\_memsec.php | роли в корпорации |
| 2 часа | update\_orders.php | ордеры на закупку/продажу |
| 4 часа | update\_standings | стенды корпорации |
| 2 часа | update\_starbases.php | состояние посов и расчёт оставшегося времени работы |
| 2 часа | update\_wallets.php | баланс кошельков, журналы и транзакции |

Пример настройки cron:
```
#eve api site data update
10 0    * * *   wget -q -O /dev/null http://localhost/update_common.php
5 1     * * *   wget -q -O /dev/null http://localhost/update_assets.php
0 */1   * * *   wget -q -O /dev/null http://localhost/update_jobs.php
0 */2   * * *   wget -q -O /dev/null http://localhost/update_kills.php
5 */2   * * *   wget -q -O /dev/null http://localhost/update_memsec.php
0 */2   * * *   wget -q -O /dev/null http://localhost/update_orders.php
15 */4  * * *   wget -q -O /dev/null http://localhost/update_standings.php
5 */2   * * *   wget -q -O /dev/null http://localhost/update_starbases.php
0 */2   * * *   wget -q -O /dev/null http://localhost/update_wallets.php
```