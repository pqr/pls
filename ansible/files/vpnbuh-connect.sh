#!/bin/bash
vpn='{{ vpnbuh_ip }}' #внешний адрес или имя вашего pptp сервера
echo connecting $vpn
ps=`ps ax | grep $vpn | wc -l`
if [ $ps -ge 2 ]; then
exit 1;
else sudo /usr/sbin/pppd call vpnbuh
exit 0;
fi
