#!/bin/sh
. /etc/rc.conf
. ${cbsd_workdir}/nc.inventory

vnc_port=$( head -n1 ${cbsd_workdir}/jails-system/${jname}/vnc_port | awk '{printf $1}' )
port=$(( vnc_port + 3000 ))

if [ "${vm_os_type}" = "windows" ]; then
	/usr/local/bin/cbsd expose mode=add jname=${jname} in=${port} out=3389
else
	/usr/local/bin/cbsd expose mode=add jname=${jname} in=${port} out=22
fi
echo "${nodeip} -p${port}" > ${cbsd_workdir}/jails-system/${jname}/vnc_port2

exit 0
