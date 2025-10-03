# OAuth2 Setup with Keycloak

This guide explains how to secure your MCP server with OAuth2 using Keycloak for remote access with Dynamic Client Registration (DCR).

## Why OAuth2?

OAuth2 allows you to:
- **Share** the MCP server with your team securely
- **Control access** centrally (who can connect, which email domains, etc.)
- **Trace** all actions (user identification in logs)
- **Deploy remotely** instead of local-only stdio

## Prerequisites

- Docker (for Keycloak)
- Your MCP server running on HTTP (e.g., `http://127.0.0.1:8000`)

## Step 1: Start Keycloak

```bash
docker run -d \
  --name keycloak \
  -p 8081:8080 \
  -e KC_BOOTSTRAP_ADMIN_USERNAME=admin \
  -e KC_BOOTSTRAP_ADMIN_PASSWORD=admin \
  quay.io/keycloak/keycloak:latest \
  start-dev
```

Access Keycloak admin: http://localhost:8081 (admin/admin)

## Step 2: Configure Keycloak (Using master realm)

### Login to Keycloak CLI

```bash
docker exec keycloak /opt/keycloak/bin/kcadm.sh config credentials \
  --server http://localhost:8080 \
  --realm master \
  --user admin \
  --password admin
```

### Create Client Scope with Audience Mapper

MCP servers require an `audience` claim in tokens. Create a default client scope:

```bash
# Create client scope
SCOPE_ID=$(docker exec keycloak /opt/keycloak/bin/kcadm.sh create client-scopes -r master \
  -s name=mcp-audience \
  -s protocol=openid-connect \
  -s 'attributes."include.in.token.scope"=true' \
  -s 'attributes."display.on.consent.screen"=false' \
  -i)

# Add audience mapper
docker exec keycloak /opt/keycloak/bin/kcadm.sh create \
  client-scopes/$SCOPE_ID/protocol-mappers/models -r master \
  -s name=mcp-audience-mapper \
  -s protocol=openid-connect \
  -s protocolMapper=oidc-audience-mapper \
  -s 'config."included.client.audience"="http://127.0.0.1:8000/mcp"' \
  -s 'config."access.token.claim"="true"'

# Set as default scope
docker exec keycloak /opt/keycloak/bin/kcadm.sh update \
  realms/master/default-default-client-scopes/$SCOPE_ID -r master
```

### Enable Dynamic Client Registration

Remove "Trusted Hosts" policy to allow anonymous DCR:

```bash
# Get policy ID and delete it
docker exec keycloak /opt/keycloak/bin/kcadm.sh get components -r master > /tmp/components.json
POLICY_ID=$(cat /tmp/components.json | jq -r '.[] | select(.name == "Trusted Hosts") | .id')
docker exec keycloak /opt/keycloak/bin/kcadm.sh delete components/$POLICY_ID -r master
```

**Note**: You'll use the existing `admin` user (admin/admin) for authentication.

## Step 3: Configure MCP Server

Update your `.env` file:

```bash
###> OAuth Configuration ###
KEYCLOAK_URL=http://localhost:8081
KEYCLOAK_REALM=master
KEYCLOAK_AUDIENCE=http://127.0.0.1:8000/mcp
###< OAuth Configuration ###
```

## Step 4: Configure MCP Client

Update your `.mcp.json`:

```json
{
  "mcpServers": {
    "redmine": {
      "url": "http://127.0.0.1:8000/mcp",
      "transport": "http"
    }
  }
}
```

**That's it!** The client will:
1. Discover Keycloak via `/.well-known/oauth-protected-resource`
2. Register automatically via DCR
3. Open your browser for authentication (admin/admin)
4. Get an access token and use it for all requests

## How It Works

### 1. Discovery
Client calls `GET /.well-known/oauth-protected-resource`:
```json
{
  "resource": "http://127.0.0.1:8000/mcp",
  "authorization_servers": ["http://localhost:8081/realms/master"]
}
```

### 2. Dynamic Client Registration
Client calls `POST http://localhost:8081/realms/master/clients-registrations/openid-connect`:
```json
{
  "client_name": "MCP Client",
  "redirect_uris": ["http://localhost:PORT/callback"],
  "grant_types": ["authorization_code"],
  "token_endpoint_auth_method": "none"
}
```

Gets back a `client_id`.

### 3. OAuth Flow (PKCE)
- Authorization: User authenticates in browser
- Token: Client gets JWT access token
- Requests: Token sent in `Authorization: Bearer <token>` header

### 4. Token Validation
Server validates:
- Signature (RS256 with JWKS public keys)
- Audience (`http://127.0.0.1:8000/mcp`)
- Expiration

## Production Deployment

For production:

1. **Use HTTPS**: Update `KEYCLOAK_URL` and `KEYCLOAK_AUDIENCE` with real domain
2. **Configure realm**: Add identity providers (Google, SAML, etc.)
3. **Restrict access**: Create groups, roles, email domain restrictions
4. **Monitor**: Enable Keycloak events and logging

Example production `.env`:
```bash
KEYCLOAK_URL=https://auth.your-company.com
KEYCLOAK_REALM=production
KEYCLOAK_AUDIENCE=https://mcp.your-company.com/mcp
```

## Troubleshooting

### "Invalid audience" error
- Verify client scope is set as default
- Check audience mapper configuration
- Reconnect client (delete and re-add in `.mcp.json`)

### "Invalid redirect_uri" error
- Add wildcard redirect URI to client: `http://localhost:*/callback`

### "Failed to load JWKS" error
- Check Keycloak is accessible from MCP server
- Verify `KEYCLOAK_URL` in `.env`

## Resources

- [MCP Specification](https://modelcontextprotocol.io)
- [RFC 7591 - Dynamic Client Registration](https://datatracker.ietf.org/doc/html/rfc7591)
- [RFC 9728 - OAuth 2.0 Protected Resource Metadata](https://datatracker.ietf.org/doc/html/rfc9728)
- [Keycloak Documentation](https://www.keycloak.org/documentation)
