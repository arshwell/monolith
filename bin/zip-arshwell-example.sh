## Archive folder: monolith/example/ in monolith/example.tar.gz ##

BASEDIR=$(dirname $0)
MONOLITH=$(dirname $BASEDIR) # monolith path

echo "";

tar --exclude-vcs --exclude-vcs-ignores --no-delay-directory-restore --no-same-permissions --directory="${MONOLITH}" -cf "${MONOLITH}/example.tar" "example"

# "--exclude-vcs-ignores" doesn't work properly,
# that's because "tar" interprets a bit different the .git syntax.
# so we need to delete them manually:
tar -vf "${MONOLITH}/example.tar" --delete "example/vendor"
tar -vf "${MONOLITH}/example.tar" --delete "example/composer.lock"

tar --append --no-same-permissions --directory="${MONOLITH}" -f "${MONOLITH}/example.tar" "example/.gitignore"

gzip -fv "${MONOLITH}/example.tar"
