## Archive folder: monolith/example/ in monolith/example.tar ##

BASEDIR=$(dirname $0)
MONOLITH=$(dirname $BASEDIR) # monolith path

echo "";

tar --exclude-vcs --exclude-vcs-ignores --no-delay-directory-restore --no-same-permissions --directory="${MONOLITH}" -cf "${MONOLITH}/example.tar" "example"

tar --append --no-same-permissions --directory="${MONOLITH}" -f "${MONOLITH}/example.tar" "example/.gitignore"

gzip -fv "${MONOLITH}/example.tar"
