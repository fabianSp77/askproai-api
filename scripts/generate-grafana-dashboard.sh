#!/bin/bash

#############################################################################
# Generate and Deploy Grafana Dashboard for MCP Monitoring
#############################################################################

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CONFIG_DIR="$PROJECT_DIR/config"
DASHBOARD_FILE="$CONFIG_DIR/grafana-mcp-dashboard.json"
GRAFANA_URL="${GRAFANA_URL:-http://localhost:3000}"
GRAFANA_API_KEY="${GRAFANA_API_KEY:-}"
GRAFANA_USER="${GRAFANA_USER:-admin}"
GRAFANA_PASSWORD="${GRAFANA_PASSWORD:-admin123}"

echo "Generating Grafana dashboard for MCP monitoring..."

# Source environment variables
if [[ -f "$PROJECT_DIR/.env" ]]; then
    set -a
    source "$PROJECT_DIR/.env"
    set +a
fi

APP_URL="${APP_URL:-https://api.askproai.de}"
APP_ENV="${APP_ENV:-production}"

echo "Configuring dashboard for environment: $APP_ENV"
echo "Application URL: $APP_URL"

# Check if dashboard file exists
if [[ ! -f "$DASHBOARD_FILE" ]]; then
    echo "Dashboard file not found: $DASHBOARD_FILE"
    exit 1
fi

# Function to create Grafana datasource
create_datasource() {
    local datasource_config='{
      "name": "AskProAI Prometheus",
      "type": "prometheus",
      "url": "http://prometheus:9090",
      "access": "proxy",
      "isDefault": true,
      "jsonData": {
        "timeInterval": "15s",
        "queryTimeout": "60s",
        "httpMethod": "POST"
      }
    }'
    
    echo "Creating Prometheus datasource..."
    
    if [[ -n "$GRAFANA_API_KEY" ]]; then
        # Use API key authentication
        response=$(curl -s -X POST \
            -H "Authorization: Bearer $GRAFANA_API_KEY" \
            -H "Content-Type: application/json" \
            -d "$datasource_config" \
            "$GRAFANA_URL/api/datasources" 2>/dev/null || echo "failed")
    else
        # Use basic authentication
        response=$(curl -s -X POST \
            -u "$GRAFANA_USER:$GRAFANA_PASSWORD" \
            -H "Content-Type: application/json" \
            -d "$datasource_config" \
            "$GRAFANA_URL/api/datasources" 2>/dev/null || echo "failed")
    fi
    
    if [[ "$response" == *"success"* ]] || [[ "$response" == *"Datasource added"* ]]; then
        echo "Datasource created successfully"
    elif [[ "$response" == *"already exists"* ]]; then
        echo "Datasource already exists"
    else
        echo "Warning: Datasource creation may have failed: $response"
    fi
}

# Function to import dashboard
import_dashboard() {
    echo "Importing MCP monitoring dashboard..."
    
    # Add timestamp to dashboard title to avoid conflicts
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local dashboard_json=$(cat "$DASHBOARD_FILE")
    
    # Update dashboard with current environment
    local updated_dashboard=$(echo "$dashboard_json" | jq --arg env "$APP_ENV" --arg url "$APP_URL" --arg ts "$timestamp" '
        .dashboard.title = "AskProAI MCP vs Webhook (" + $env + " - " + $ts + ")" |
        .dashboard.tags += [$env] |
        .dashboard.links[0].url = $url + "/api/mcp/retell/health" |
        .dashboard.links[1].url = $url + "/admin/system-monitoring"
    ')
    
    if [[ -n "$GRAFANA_API_KEY" ]]; then
        # Use API key authentication
        response=$(curl -s -X POST \
            -H "Authorization: Bearer $GRAFANA_API_KEY" \
            -H "Content-Type: application/json" \
            -d "$updated_dashboard" \
            "$GRAFANA_URL/api/dashboards/db" 2>/dev/null || echo "failed")
    else
        # Use basic authentication
        response=$(curl -s -X POST \
            -u "$GRAFANA_USER:$GRAFANA_PASSWORD" \
            -H "Content-Type: application/json" \
            -d "$updated_dashboard" \
            "$GRAFANA_URL/api/dashboards/db" 2>/dev/null || echo "failed")
    fi
    
    if [[ "$response" == *"success"* ]]; then
        local dashboard_id=$(echo "$response" | jq -r '.id // empty')
        local dashboard_uid=$(echo "$response" | jq -r '.uid // empty')
        echo "Dashboard imported successfully"
        echo "Dashboard ID: $dashboard_id"
        echo "Dashboard UID: $dashboard_uid"
        echo "Dashboard URL: $GRAFANA_URL/d/$dashboard_uid"
    else
        echo "Warning: Dashboard import may have failed: $response"
    fi
}

# Function to set up alerting
setup_alerting() {
    echo "Setting up Grafana alerting..."
    
    # Create notification channel for MCP alerts
    local notification_config='{
      "name": "mcp-alerts",
      "type": "webhook",
      "settings": {
        "url": "'$APP_URL'/api/alerts/grafana",
        "httpMethod": "POST",
        "title": "MCP Alert: {{ range .Alerts }}{{ .Annotations.summary }}{{ end }}",
        "text": "{{ range .Alerts }}{{ .Annotations.description }}{{ end }}"
      }
    }'
    
    if [[ -n "$GRAFANA_API_KEY" ]]; then
        curl -s -X POST \
            -H "Authorization: Bearer $GRAFANA_API_KEY" \
            -H "Content-Type: application/json" \
            -d "$notification_config" \
            "$GRAFANA_URL/api/alert-notifications" > /dev/null || true
    else
        curl -s -X POST \
            -u "$GRAFANA_USER:$GRAFANA_PASSWORD" \
            -H "Content-Type: application/json" \
            -d "$notification_config" \
            "$GRAFANA_URL/api/alert-notifications" > /dev/null || true
    fi
    
    echo "Alerting notification channel configured"
}

# Function to create organization and user
setup_organization() {
    echo "Setting up Grafana organization for AskProAI..."
    
    # Create organization
    local org_config='{
      "name": "AskProAI"
    }'
    
    if [[ -n "$GRAFANA_API_KEY" ]]; then
        curl -s -X POST \
            -H "Authorization: Bearer $GRAFANA_API_KEY" \
            -H "Content-Type: application/json" \
            -d "$org_config" \
            "$GRAFANA_URL/api/orgs" > /dev/null 2>&1 || true
    else
        curl -s -X POST \
            -u "$GRAFANA_USER:$GRAFANA_PASSWORD" \
            -H "Content-Type: application/json" \
            -d "$org_config" \
            "$GRAFANA_URL/api/orgs" > /dev/null 2>&1 || true
    fi
    
    echo "Organization setup completed"
}

# Function to check Grafana connectivity
check_grafana_connectivity() {
    echo "Checking Grafana connectivity..."
    
    local health_check
    if [[ -n "$GRAFANA_API_KEY" ]]; then
        health_check=$(curl -s -H "Authorization: Bearer $GRAFANA_API_KEY" \
            "$GRAFANA_URL/api/health" 2>/dev/null || echo "failed")
    else
        health_check=$(curl -s -u "$GRAFANA_USER:$GRAFANA_PASSWORD" \
            "$GRAFANA_URL/api/health" 2>/dev/null || echo "failed")
    fi
    
    if [[ "$health_check" == *"ok"* ]]; then
        echo "Grafana is accessible at $GRAFANA_URL"
        return 0
    else
        echo "Warning: Cannot connect to Grafana at $GRAFANA_URL"
        echo "Health check response: $health_check"
        return 1
    fi
}

# Function to generate dashboard provisioning config
generate_provisioning_config() {
    echo "Generating Grafana provisioning configuration..."
    
    mkdir -p "$CONFIG_DIR/grafana/provisioning/dashboards"
    mkdir -p "$CONFIG_DIR/grafana/provisioning/datasources"
    
    # Dashboard provisioning
    cat > "$CONFIG_DIR/grafana/provisioning/dashboards/mcp.yml" << EOF
apiVersion: 1

providers:
  - name: 'AskProAI MCP'
    orgId: 1
    folder: 'MCP Monitoring'
    type: file
    disableDeletion: false
    updateIntervalSeconds: 10
    options:
      path: /etc/grafana/provisioning/dashboards
EOF
    
    # Copy dashboard file
    cp "$DASHBOARD_FILE" "$CONFIG_DIR/grafana/provisioning/dashboards/"
    
    # Datasource provisioning
    cat > "$CONFIG_DIR/grafana/provisioning/datasources/prometheus.yml" << EOF
apiVersion: 1

datasources:
  - name: AskProAI Prometheus
    type: prometheus
    access: proxy
    url: http://prometheus:9090
    isDefault: true
    editable: true
    jsonData:
      timeInterval: '15s'
      queryTimeout: '60s'
      httpMethod: POST
EOF
    
    echo "Provisioning configuration generated in $CONFIG_DIR/grafana/"
}

# Function to export current dashboard
export_dashboard() {
    local dashboard_uid="$1"
    local export_file="$CONFIG_DIR/grafana-mcp-dashboard-export-$(date +%Y%m%d_%H%M%S).json"
    
    echo "Exporting dashboard UID: $dashboard_uid"
    
    if [[ -n "$GRAFANA_API_KEY" ]]; then
        curl -s -H "Authorization: Bearer $GRAFANA_API_KEY" \
            "$GRAFANA_URL/api/dashboards/uid/$dashboard_uid" \
            | jq '.dashboard' > "$export_file"
    else
        curl -s -u "$GRAFANA_USER:$GRAFANA_PASSWORD" \
            "$GRAFANA_URL/api/dashboards/uid/$dashboard_uid" \
            | jq '.dashboard' > "$export_file"
    fi
    
    if [[ -f "$export_file" ]] && [[ -s "$export_file" ]]; then
        echo "Dashboard exported to: $export_file"
    else
        echo "Dashboard export failed"
    fi
}

# Main execution
main() {
    local action="${1:-deploy}"
    
    case $action in
        deploy)
            echo "Deploying MCP monitoring dashboard to Grafana..."
            
            if check_grafana_connectivity; then
                setup_organization
                create_datasource
                import_dashboard
                setup_alerting
                echo "Dashboard deployment completed successfully!"
            else
                echo "Grafana deployment skipped due to connectivity issues"
                echo "Generating provisioning configuration instead..."
                generate_provisioning_config
            fi
            ;;
        provision)
            echo "Generating Grafana provisioning configuration..."
            generate_provisioning_config
            echo "Provisioning configuration generated"
            ;;
        export)
            local uid="${2:-}"
            if [[ -z "$uid" ]]; then
                echo "Usage: $0 export <dashboard-uid>"
                exit 1
            fi
            export_dashboard "$uid"
            ;;
        test)
            echo "Testing Grafana connectivity..."
            if check_grafana_connectivity; then
                echo "Grafana test successful"
            else
                echo "Grafana test failed"
                exit 1
            fi
            ;;
        *)
            echo "Usage: $0 [deploy|provision|export|test] [dashboard-uid]"
            echo "  deploy    - Deploy dashboard to running Grafana instance"
            echo "  provision - Generate provisioning configuration files"
            echo "  export    - Export existing dashboard by UID"
            echo "  test      - Test Grafana connectivity"
            exit 1
            ;;
    esac
}

# Script execution
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi