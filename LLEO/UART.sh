#!/bin/bash

sudo chmod 666 /dev/ttyUSB*
sudo chmod 666 /dev/ttyACM*

ls -l /dev/ttyUSB*
ls -l /dev/ttyACM*

DEV="/dev/ttyUSB0"

sudo stty -F $DEV raw echo 115200
sudo stty -F $DEV -a
cat $DEV
# | hexdump -C
exit

stty [-F DEVICE | --file=DEVICE] [-g|--save]


stty raw должен работать, ну или в C man termios и кучу констант ручками.
Если строки заканчивающиеся на \n читаются, а остальные данные нет, то, скорее всего, канонический ввод включен.
Надо его выключить (битик ICANON в c_lflag сбросить) и выставить VMIN == 1 и VTIME == 0.

(Ну и, разумеется, еще про один уровень буферизации не забывать если вдруг вместо read используется fread. Не знаю зачем это могло бы быть нужно использовать fread, но setvbuf поможет отключить буферизацию.)