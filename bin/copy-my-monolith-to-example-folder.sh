echo "";
echo "Copy Arshwell files to example project's Arshwell ⇩"
echo "";

# This command...
#
# ln -sfn "$(pwd)/vendor/arshwell/monolith/[subfolder]/" "$(pwd)/vendor/arshwell/monolith/example/vendor/arshwell/monolith/"
#
# ...should link the code of your subfolder files to the ones from example project's vendor.
#
# But it looks like they are not real synced at all :(
# Note: tested on Windows 11, VSCode Git Bash.

my_arshwell_path="vendor/arshwell/monolith"
ex_project_arshwell_path="vendor/arshwell/monolith/example/vendor/arshwell/monolith"

(set -x; cp -rf "${my_arshwell_path}/bootstrap/" "${ex_project_arshwell_path}")
echo "";
(set -x; cp -rf "${my_arshwell_path}/DevTools/" "${ex_project_arshwell_path}")
echo "";
(set -x; cp -rf "${my_arshwell_path}/docs/" "${ex_project_arshwell_path}")
echo "";
(set -x; cp -rf "${my_arshwell_path}/resources/" "${ex_project_arshwell_path}")
echo "";
(set -x; cp -rf "${my_arshwell_path}/src/" "${ex_project_arshwell_path}")
