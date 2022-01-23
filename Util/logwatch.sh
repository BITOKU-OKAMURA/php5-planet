#!/bin/dash
Logfile=$(ls -ltra ./Log/|grep log|tail -1|awk {'print $9'}|tail -1)
tail -2000f ./Log/$Logfile

