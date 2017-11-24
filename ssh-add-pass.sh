#!/bin/bash

if [ $# -ne 2 ] ; then
  echo "Usage: ssh-add-pass keyfile passfile"
  exit 1
fi

#eval $(ssh-agent)
pass=$(cat $2)
echo "Key added"
expect << EOF
  spawn ssh-add $1
  expect "Enter passphrase"
  send "$pass\r"
  expect eof
EOF
