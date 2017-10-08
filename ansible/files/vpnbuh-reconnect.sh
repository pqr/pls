#!/bin/bash
if ! ping -Q 2 -c 4 -t 2 {{ vpnbuh_gateway }}; then
sudo killall pppd
sudo /home/ubuntu/vpnbuh-connect.sh
fi
