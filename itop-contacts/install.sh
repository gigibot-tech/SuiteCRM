#!/bin/sh
# Copy the iTop contact link into SuiteCRM's custom directory, then run Quick Repair and Rebuild.
set -eu
root=$(cd "$(dirname "$0")/.." && pwd)
src=$(cd "$(dirname "$0")" && pwd)
mkdir -p "$root/custom/Extension/modules/Contacts/Ext/Vardefs" \
  "$root/custom/Extension/modules/Contacts/Ext/Language" \
  "$root/custom/modules/Contacts"
cp "$src/Extension/modules/Contacts/Ext/Vardefs/itop_person_id.php" "$root/custom/Extension/modules/Contacts/Ext/Vardefs/"
cp "$src/Extension/modules/Contacts/Ext/Language/en_us.itop_person_id.php" "$root/custom/Extension/modules/Contacts/Ext/Language/"
cp "$src/modules/Contacts/logic_hooks.php" "$root/custom/modules/Contacts/"
cp "$src/modules/Contacts/ItopContactLink.php" "$root/custom/modules/Contacts/"
if [ ! -f "$root/custom/itop_link.php" ]; then
  cp "$src/itop_link.example.php" "$root/custom/itop_link.example.php"
fi
echo "Copied the iTop contact link into custom/. Run Quick Repair and Rebuild in SuiteCRM."
