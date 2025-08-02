#!/bin/bash
# =============================================================================
# Firewall Rules Configuration for AskProAI
# =============================================================================
# CRITICAL: Test rules carefully before applying in production!
# Usage: chmod +x firewall-rules.sh && sudo ./firewall-rules.sh

set -e

echo "🔥 Setting up AskProAI Firewall Rules..."

# -----------------------------------------------------------------------------
# UFW (Uncomplicated Firewall) Configuration
# -----------------------------------------------------------------------------

# Reset UFW to defaults
ufw --force reset

# Set default policies
ufw default deny incoming
ufw default allow outgoing

# -----------------------------------------------------------------------------
# Essential Services
# -----------------------------------------------------------------------------

# SSH (adjust port if using non-standard)
ufw allow 22/tcp comment "SSH"

# HTTP/HTTPS
ufw allow 80/tcp comment "HTTP"
ufw allow 443/tcp comment "HTTPS"

# MySQL (localhost only)
ufw allow from 127.0.0.1 to any port 3306 comment "MySQL localhost"

# Redis (localhost only)
ufw allow from 127.0.0.1 to any port 6379 comment "Redis localhost"

# -----------------------------------------------------------------------------
# Webhook IP Whitelisting
# -----------------------------------------------------------------------------

# Retell.ai webhook IPs
RETELL_IPS=(
    "54.156.46.171"
    "3.226.44.241" 
    "18.215.226.36"
    "100.25.154.111"
)

echo "📞 Adding Retell.ai webhook IPs..."
for ip in "${RETELL_IPS[@]}"; do
    ufw allow from $ip to any port 443 comment "Retell.ai webhook"
done

# Cal.com webhook IPs (AWS regions they typically use)
CALCOM_CIDRS=(
    "3.128.0.0/9"
    "18.216.0.0/12"
    "52.0.0.0/8"
)

echo "📅 Adding Cal.com webhook CIDR ranges..."
for cidr in "${CALCOM_CIDRS[@]}"; do
    ufw allow from $cidr to any port 443 comment "Cal.com webhook"
done

# Stripe webhook IPs
STRIPE_IPS=(
    "54.187.174.169"
    "54.187.205.235"
    "54.187.216.72"
    "54.241.31.99"
    "54.241.31.102"
)

echo "💳 Adding Stripe webhook IPs..."
for ip in "${STRIPE_IPS[@]}"; do
    ufw allow from $ip to any port 443 comment "Stripe webhook"
done

# -----------------------------------------------------------------------------
# Admin Access Restrictions (Optional - Uncomment to enable)
# -----------------------------------------------------------------------------

# Define admin IP ranges (customize these!)
ADMIN_IPS=(
    # "192.168.1.0/24"    # Local network
    # "10.0.0.0/8"        # VPN range  
    # "YOUR.PUBLIC.IP.HERE/32"  # Your office IP
)

# Enable admin IP restrictions (commented out by default)
# echo "👨‍💼 Adding admin IP restrictions..."
# for ip in "${ADMIN_IPS[@]}"; do
#     ufw allow from $ip to any port 443 comment "Admin access"
# done

# -----------------------------------------------------------------------------
# Rate Limiting with UFW
# -----------------------------------------------------------------------------

# Limit SSH connections (prevent brute force)
ufw limit ssh comment "SSH rate limit"

# Limit HTTP connections per IP
ufw limit 80/tcp comment "HTTP rate limit"
ufw limit 443/tcp comment "HTTPS rate limit"

# -----------------------------------------------------------------------------
# Additional Security Rules
# -----------------------------------------------------------------------------

# Block common attack ports
BLOCKED_PORTS=(21 23 25 110 143 993 995 1433 3389 5432 5984 6379 27017)

echo "🚫 Blocking common attack ports..."
for port in "${BLOCKED_PORTS[@]}"; do
    ufw deny $port comment "Block common attack port"
done

# Block Tor exit nodes (optional - uncomment to enable)
# echo "🧅 Blocking Tor exit nodes..."
# curl -s https://check.torproject.org/exit-addresses | grep ExitAddress | cut -d' ' -f2 | while read ip; do
#     ufw deny from $ip comment "Tor exit node"
# done

# -----------------------------------------------------------------------------
# Enable UFW
# -----------------------------------------------------------------------------

echo "✅ Enabling UFW firewall..."
ufw --force enable

# Show status
echo "📊 Current UFW status:"
ufw status numbered

# -----------------------------------------------------------------------------
# IPTables Additional Rules (Advanced)
# -----------------------------------------------------------------------------

echo "🔧 Adding advanced iptables rules..."

# Drop invalid packets
iptables -I INPUT -m state --state INVALID -j DROP

# Drop TCP packets that are new and are not SYN
iptables -I INPUT -p tcp ! --syn -m state --state NEW -j DROP

# Drop SYN packets with suspicious MSS value
iptables -I INPUT -p tcp -m tcp --tcp-flags SYN,RST SYN,RST -j DROP

# Block fragments
iptables -I INPUT -f -j DROP

# Block null packets
iptables -I INPUT -p tcp --tcp-flags ALL NONE -j DROP

# Block XMAS packets
iptables -I INPUT -p tcp --tcp-flags ALL ALL -j DROP

# Limit ICMP ping requests
iptables -A INPUT -p icmp --icmp-type echo-request -m limit --limit 1/second -j ACCEPT
iptables -A INPUT -p icmp --icmp-type echo-request -j DROP

# Protection against port scanning
iptables -N port-scanning
iptables -A port-scanning -p tcp --tcp-flags SYN,ACK,FIN,RST RST -m limit --limit 1/s --limit-burst 2 -j RETURN
iptables -A port-scanning -j DROP

# Log dropped packets (optional - can generate lots of logs)
# iptables -A INPUT -j LOG --log-prefix "UFW DROPPED: " --log-level 4

# Save iptables rules
iptables-save > /etc/iptables/rules.v4

# -----------------------------------------------------------------------------
# IPv6 Rules (if IPv6 is enabled)
# -----------------------------------------------------------------------------

echo "🌐 Configuring IPv6 rules..."

# Similar rules for IPv6
ip6tables -I INPUT -m state --state INVALID -j DROP
ip6tables -A INPUT -p icmpv6 --icmpv6-type echo-request -m limit --limit 1/second -j ACCEPT
ip6tables -A INPUT -p icmpv6 --icmpv6-type echo-request -j DROP

# Save IPv6 rules
ip6tables-save > /etc/iptables/rules.v6

echo "🔐 Firewall configuration completed!"
echo "⚠️  IMPORTANT: Test your connections now!"
echo "⚠️  If locked out, reboot server to reset rules!"

# -----------------------------------------------------------------------------
# Firewall Status Check
# -----------------------------------------------------------------------------

echo "📋 Final firewall status:"
ufw status verbose

echo ""
echo "🔍 Active iptables rules:"
iptables -L -n --line-numbers

echo ""
echo "✅ Firewall setup complete!"
echo "📝 Log files: /var/log/ufw.log"
echo "🔧 Manage rules: ufw status numbered"