Vbox Virtualization Utls
==================================================


A set of PHP scripts created to manage Virtual Box virtualization server.
Basically it's a wrapper around vboxmanage command to simplify some daily tasks. 

Includes:
 1) **startvms.php** Batch starting virtual machines
 1) **stopvms.php** Batch stopping virtual machines
 2) **info.php**  Displaying information about running virtual machines
 3) **backups.php** Backing up virtual machines and uploading them to a FTP server. Also displays info about recent backups and used storage.
 4) **vmutils** A service script for Unix operating systems to start the backup daemon.
 
 

A sample config file can be found in /config/config.

Usage:
```
php info.php
``` 
This command displays info about running vms:
```
Name:            frontend-server
OS type:         Debian
State:           running (since 2020-08-21T09:05:28.295000000)
Memory size:     128MB
CPU exec cap:    100%
VRAM size:       12MB
NIC 1:           MAC: 080027F5CC00

Name:            mysql
OS type:         Debian
State:           running (since 2020-08-21T09:06:58.141000000)
Memory size:     512MB
CPU exec cap:    50%
VRAM size:       12MB
NIC 1:           MAC: 08002704485A
```

**backups.php** command has subcomands:

```
php backups.php backup //backups virtual machines to the ftp server specified in the config
php backups.php storage info //show information about available backups
php backups.php storage clear 3 //clears storage and leaves 3 latest backups
php backups.php storage fix //clears storage from empty backups which happen if storage is full
``` 


**For developers:**

**/lib/Helper.php** contains all the business logic for the project. Comments are yet to be translated to English. 