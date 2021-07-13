# Описание плейбуков #

Данный репозиторий описывает автоматизированный деплой web-сервисов, используется концепция IaC (инфраструктура как код). Для автоматизации используется ansible.


На рабочий ПК или ноутбук необходимо установить ansible, например, через `brew`:

~~~
% brew install ansible         
~~~


## Как пользоваться переменными `vars_decrypted.yml`

Файл описывает переменные, которые не требуют шифрования. Переменные используются всеми плейбуками.

~~~
php_version: 7.1
~~~

## Как пользоваться переменными `vars_encrypted.yml`

Файл описывает sensitive переменные, которые должны быть зашифрованы: логины, пароли, e-mail, ssh-ключи и данные для входа.

Для того чтобы его расшифровать, необходимо выполнить команду (будет запрошен vault-pass):

~~~
$ ansible-vault decrypt vars_encrypted.yml
Decryption successful
~~~

Файл расшифрован. Его можно изменять/дополнять и после этого обратно зашифровать (будет запрошен vault-pass):

~~~
$ ansible-vault encrypt vars_encrypted.yml
Encryption successful
~~~

Если нечаянно выполнен `git push` незашифрованного файла - рекомендуется поменять все sensitive данные. 

* Изменение vault-pass:  
 1) расшифровать файл, указав **старый** пароль  
 2) зашифровать файл, указав **новый** пароль  

Пароль можно сохранить в `.vault-pass` файле. После этого в файле `ansible.cfg` необходимо указать:

~~~
vault_password_file = .vault-pass
~~~


## Как пользоваться `inventory.*` файлами

Файлы описывает хосты на которые ansible автоматически задеплоит нужную конфигурацию. Добавление новых хостов/файлов - по образу и подобию.

 - `inventory.prod`: рабочий web-сайт

## Особенности AWS

* Изначально на AMI образе от Amazon нет даже python.
Его потребуется ровно 1 раз установить командой:

~~~
sudo apt install python
~~~

 * Подключение отдельного хранилища, например, `xvdf`:

 0. Проверить имя устройсва командой `lsblk` - удостовериться, что это `xvdf`

 1. Создать на пустом диске файловую систему, если это новый диск 
    (если же прикрепляем volume, который уже был, то этот шаг можно пропустить)
 
~~~
sudo mkfs -t ext4 /dev/xvdf
~~~
 
 2. Создать папку куда будет система примонтирована:

~~~
mkdir /home/ubuntu/files
~~~
 
3. Примонтировать эту файловую систему в созданную папку:

~~~
sudo mount /dev/xvdf /home/ubuntu/files
~~~

4. Проверить что получилось и сконфигурировать `/etc/fstab`

~~~
root@ip-172-31-22-0:/home/ubuntu# df -h
Filesystem      Size  Used Avail Use% Mounted on
udev            2.0G     0  2.0G   0% /dev
tmpfs           396M   41M  355M  11% /run
/dev/xvda1      7.7G  1.2G  6.6G  16% /
tmpfs           2.0G     0  2.0G   0% /dev/shm
tmpfs           5.0M     0  5.0M   0% /run/lock
tmpfs           2.0G     0  2.0G   0% /sys/fs/cgroup
tmpfs           396M     0  396M   0% /run/user/1000
/dev/xvdf        15G   38M   14G   1% /home/ubuntu/files
~~~

В файл `/etc/fstab` добавить новую запись: `/dev/xvdf  /home/ubuntu/files  ext4     noatime  0 0`

После добавления этой новой строчки файл будет выглядеть так:

~~~
# cat /etc/fstab 
LABEL=cloudimg-rootfs	/	 ext4	defaults,discard	0 0
/dev/xvdf  /home/ubuntu/files  ext4     noatime  0 0
~~~

\* Имя диска (`xvdf`) или папки (`/home/ubuntu/files`) определяются на месте. Для записи в папку файлов необходимо, чтобы у неё был владелец и группа `ubuntu`, и чтобы в папку мог писать nginx и при этом insync - режим `0777`

~~~
ubuntu@ip-172-31-22-0:~$ sudo chmod 777 /home/ubuntu/files
ubuntu@ip-172-31-22-0:~$ sudo chown ubuntu:ubuntu /home/ubuntu/files
~~~

## Как проверить наличие пользователей: ##

* Ansible подключается к инстансу пользователем, определенным в `inventory.*` под переменной `ansible_ssh_user`.

* Данному пользователю должно быть разрешено sudo без пароля (для удобства), обычно на AMI инстансах это сделано, но если не сделано - необходимо созать в папке /etc/sudoers.d/ файл `user-ubuntu` вида:

~~~
# sudo user ubuntu for management and deploy

ubuntu ALL=(ALL) NOPASSWD:ALL
~~~

После этого у пользователя `ubuntu` команда `sudo` будет работать без ввода пароля.


## Как пользоваться плейбуками: ##

В директории находятся различные плейбуки, которые являются по сути сценариями выполнения автонастройки сервера.

~~~
playbook_deploy_all.yml         <- выполнить все плейбуки
playbook_deploy_initial.yml     <- установить базовые настройки
playbook_deploy_monitoring.yml  <- установить мониторинг
playbook_deploy_package.yml     <- установить нужные deb пакеты
playbook_deploy_toolkit.yml     <- установить рабочее окружение
playbook_deploy_webserver.yml   <- установить конфигурации nginx,php и web-сайт
~~~


**Выполнение всех плейбуков на `inventory.prod`:**

~~~
ansible-playbook playbook_deploy_all.yml -i inventory.prod
~~~

\* данный плейбук не содержит никаких команд, содержит только `include` опции. Если требуется обновить конфигурацию или пакеты - достаточно пользоваться отдельными плейбуками, это значительно ускорит и упростит работу.

**Установить весь необходимый софт на `inventory.prod`:**

~~~
ansible-playbook playbook_deploy_all.yml -i inventory.prod
~~~

**Установить (обновить) все необходимые конфигурации на `inventory.prod`:**

~~~
ansible-playbook playbook_deploy_webserver.yml  -i inventory.prod
~~~

**Установить (обновить) только конфигурации nginx и php на `inventory.devel`:**

~~~
ansible-playbook playbook_deploy_webserver.yml  -i inventory.prod --tags=nginx,php
~~~

**Переклонировать сайт на `inventory.prod` (папки должны быть созданы):**

~~~
ansible-playbook playbook_deploy_webserver.yml  -i inventory.prod --tags=git
~~~


**Установить и настроить рабочее окружение на `inventory.prod` **

~~~
ansible-playbook playbook_deploy_toolkit.yml -i inventory.prod
~~~

**Установить и настроить мониторинг на `inventory.prod`**

~~~
ansible-playbook playbook_deploy_monitoring.yml -i inventory.prod
~~~

Теги определяются для каждой задачи, и если нужно выполнить одну задачу - для ускорения и упрощения достаточно указать только то что нужно обновить:

~~~
-- tags=php
-- tags=nginx
~~~
