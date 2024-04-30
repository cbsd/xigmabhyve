#!/bin/sh
pgm="${0##*/}"		# Program basename
progdir="${0%/*}"	# Program directory
START_FOLDER="$( realpath ${progdir} )"

err() {
	exitval=$1
	shift
	echo "$*" 1>&2
	exit ${exitval}
}

export PATH="/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/sbin:/usr/local/bin"
CBSD_CMD=$( which cbsd 2>/dev/null )

tmpver=$( /usr/bin/uname -r )
ver=${tmpver%%-*}
majorver=${ver%%.*}
unset tmpver

if [ ! -x "${CBSD_CMD}" ]; then
	echo "No such cbsd executable, installing via pkg.."

	# official releases
	#env SIGNATURE_TYPE=none ASSUME_ALWAYS_YES=yes IGNORE_OSVERSION=yes pkg install -y tmux cbsd ttyd

	# for devel
	env SIGNATURE_TYPE=none ASSUME_ALWAYS_YES=yes IGNORE_OSVERSION=yes pkg install -y sudo libssh2 rsync jq sqlite3 tmux ttyd
	rootfs_url="https://www.bsdstore.ru/downloads/xigma/${majorver}/cbsd-14.0.9.a.pkg"
	fetch -o cbsd.pkg ${rootfs_url}
	pkg install -y ./cbsd.pkg
	rm -f ./cbsd.pkg
fi

CBSD_CMD=$( which cbsd 2>/dev/null )

[ ! -x "${CBSD_CMD}" ] && err 1 "No such cbsd executable"

#Store user's inputs
# This first checks to see that the user has supplied an argument
if [ ! -z $1 ]; then
	# The first argument will be the path that the user wants to be the root folder.
	# If this directory does not exist, it is created
	EXTENSION_ROOT="${1}"

	# This checks if the supplied argument is a directory. If it is not
	# then we will try to create it
	if [ ! -d ${START_FOLDER} ]; then
		echo "Attempting to create a new destination directory....."
		mkdir -p ${START_FOLDER} || err 1 "ERROR: Could not create directory!"
	fi
else
	# We are here because the user did not specify an alternate location. Thus, we should use the 
	# current directory as the root.
	EXTENSION_ROOT="/mnt/ext"
fi

EXTENSION_DIR="${EXTENSION_ROOT}/cbsd-bhyve"

# Make and move into the install staging folder
[ ! -d "${EXTENSION_ROOT}" ] && mkdir -p "${EXTENSION_ROOT}"
if [ ! -d ${START_FOLDER}/install_stage/cbsd-bhyve ]; then
	mkdir -p ${START_FOLDER}/install_stage/cbsd-bhyve || err 1 "ERROR: Could not create staging directory!"
fi
cd ${START_FOLDER}/install_stage/cbsd-bhyve || err 1 "ERROR: Could not access staging directory!"

echo "Retrieving the cbsdbhyve as a zip file"
fetch https://github.com/cbsd/xigmabhyve/archive/main.zip || err 1 "ERROR: Could not write to install directory!"

# Extract the files we want, stripping the leading directory, and exclude
# the git nonsense
echo "Unpacking the tarball..."
tar -xf main.zip --exclude='.git*' --strip-components 1
echo "Done!"
rm main.zip

echo "Detecting current configuration..."
. /etc/rc.subr
. /etc/configxml.subr
. /etc/util.subr

if [ ! -d "${EXTENSION_DIR}"} ]; then
	echo "Look like update extension"
	/usr/local/bin/rsync -avz ${START_FOLDER}/install_stage/cbsd-bhyve/ ${EXTENSION_DIR}/
	[ ! -d /usr/local/www/ext ] && mkdir -p /usr/local/www/ext
	[ ! -h /usr/local/www/ext/cbsd-bhyve ] && ln -sf ${EXTENSION_DIR} /usr/local/www/ext/cbsd-bhyve
	cd /usr/local/www
	# For each of the php files in the extensions folder
	for file in ${EXTENSION_DIR}/gui/*.php; do
		echo "ln -sf \"$file\" \"${file##*/}\""
		# Create link
		ln -sf "$file" "${file##*/}"
	done
	echo "Congratulations! You have fresh version."
	ACTION_MSG="Updated"
else
	echo "Look like fresh install"
	mv ${START_FOLDER}/install_stage/* ${EXTENSION_ROOT}/
	[ ! -d /usr/local/www/ext ] && mkdir -p /usr/local/www/ext
	[ ! -h /usr/local/www/ext/cbsd-bhyve ] && ln -sf ${EXTENSION_DIR} /usr/local/www/ext/cbsd-bhyve
	cd /usr/local/www
	# For each of the php files in the extensions folder
	for file in ${EXTENSION_DIR}/gui/*.php; do
		echo "ln -sf \"$file\" \"${file##*/}\""
		# Create link
		ln -sf "$file" "${file##*/}"
	done
	echo "Congratulations! The CBSD-bhyve extension was installed. Navigate to rudimentary config tab and push Save."
	ACTION_MSG="fresh installed"
fi

if [ ! -x ${EXTENSION_DIR}/bin/sbin/pfctl ]; then
	echo "Fetch system binary dependencies for FreeBSD ${majorver} [${EXTENSION_DIR}]..."

	cd ${EXTENSION_DIR}
	rootfs_url="https://www.bsdstore.ru/downloads/xigma/${majorver}/xigma-rootfs.tgz"
	fetch ${rootfs_url}
	[ ! -s ${EXTENSION_DIR}/xigma-rootfs.tgz ] && err 1 "unable to fetch ${rootfs_url}: no such ${EXTENSION_DIR}/xigma-rootfs.tgz"
	tar xfz xigma-rootfs.tgz && rm -f xigma-rootfs.tgz
	mv xigma-rootfs bin
	hash -r
fi

[ -d "${START_FOLDER}/install_stage/cbsd-bhyve" ] && rm -rf "${START_FOLDER}/install_stage/cbsd-bhyve"
rmdir ${START_FOLDER}/install_stage || true

CURRENTDATE=`date -j +"%Y-%m-%d %H:%M:%S"`
logger "[$CURRENTDATE]: Bhyve installer!: installer: ${ACTION_MSG} successfully"

exit 0
