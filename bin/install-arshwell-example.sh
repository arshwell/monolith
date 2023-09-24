## Unarchive file: monolith/example.tar.gz in root of project ##

echo "";
echo "Installing the example in your project ⇩ Good luck!"
echo "";

BASEDIR=$(dirname $0)
MONOLITH=$(dirname $BASEDIR) # monolith path

tar --strip-components=1 -C "${MONOLITH}/../../../" -xkf "${MONOLITH}/example.tar.gz"
