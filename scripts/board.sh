#!/usr/bin/env bash
set -euo pipefail

##############################################################################
# AskProAI – GitHub-Project-Helper  (Roadmap-Board)
##############################################################################

BOARD_NUM=2                                        # Nummer in der Board-URL
BOARD_ID=$(gh project view "$BOARD_NUM" --owner @me --format json | jq -r '.id')

# ── Helper:  jid  <Feld>  [Option]  → ID ─────────────────────────────────────
jid () {
  local field=$1 opt=${2:-__FIELD}                 # __FIELD = Feld-ID
  gh project field-list "$BOARD_NUM" --owner @me --format json |
  jq -r --arg f "$field" --arg o "$opt" '
    .fields                                          |
    map(select(.name==$f))[0]                        |  # Feld-Objekt
    if   $o=="__FIELD"                               # nur Feld-ID?
         then .id                                    # → Feld-ID
         else (.options[]|select(.name==$o).id)      # → Options-ID
    end'
}

# ── gepufferte IDs ───────────────────────────────────────────────────────────
STATUS_ID=$(jid Status)
TODO=$(jid Status "Todo") ; IP=$(jid Status "In Progress")
REVIEW=$(jid Status "Review") ; BLOCKED=$(jid Status "Blocked")
DONE=$(jid Status "Done")

TEAM_ID=$(jid Team)
PM=$(jid Team "PM") ; BE=$(jid Team "Backend") ; INF=$(jid Team "Infra")

HOURS_ID=$(jid "Aufwand [h]")

# ── Task-Factory  create_task "Titel" "Body" TEAM_OPT Stunden STATUS_OPT ────
create_task () {
  local id
  id=$(gh project item-create "$BOARD_NUM" --owner @me \
         --title "$1" --body "$2" --format json | jq -r '.id')

  gh project item-edit --project-id "$BOARD_ID" --id "$id" \
       --field-id "$STATUS_ID" --single-select-option-id "$5"
  gh project item-edit --project-id "$BOARD_ID" --id "$id" \
       --field-id "$TEAM_ID"   --single-select-option-id "$3"
  gh project item-edit --project-id "$BOARD_ID" --id "$id" \
       --field-id "$HOURS_ID"  --number "$4"

  # kurze Log-Ausgabe
  local col
  col=$(gh project field-list "$BOARD_NUM" --owner @me --format json |
        jq -r --arg id "$5" '
          map(select(.name=="Status"))[0].options[] |
          select(.id==$id).name')
  echo "✔  $1 – Status: $col"
}

# ── Vision-Karte ggf. nach »Todo« verschieben ───────────────────────────────
VID=$(gh project item-list "$BOARD_NUM" --owner @me --format json |
        jq -r '.items[] | select(.title=="Vision & Roadmap").id')
[[ -n "$VID" ]] && gh project item-edit --project-id "$BOARD_ID" \
                      --id "$VID" --field-id "$STATUS_ID" \
                      --single-select-option-id "$TODO" || true

# ── Beispiel-Tasks ───────────────────────────────────────────────────────────
create_task "Doku: README aktualisieren" "- Roadmap-Abschnitt ergänzt" "$PM" 1 "$DONE"
create_task "CI-Pipeline Hardening"      "- Fail fast · Notify Slack"  "$BE" 3 "$TODO"

# ── Kompakte Board-Übersicht ────────────────────────────────────────────────
echo -e "\n### Aktueller Board-Überblick"
gh project item-list "$BOARD_NUM" --owner @me --format json |
jq -r '
  .items[] |
  [.title,
   ( ([.fieldValues[]?]//[])|map(select(.field.name=="Status").name)        |first//"-"),
   ( ([.fieldValues[]?]//[])|map(select(.field.name=="Team").name)          |first//"-"),
   ( ([.fieldValues[]?]//[])|map(select(.field.name=="Aufwand [h]").number) |first//"-")
  ] | @tsv' | column -t
