# MCP Redmine Server

An MCP (Model Context Protocol) server that integrates Redmine with AI assistants like Claude Desktop, enabling natural language interaction with your Redmine instance.

## 🚀 Quick Start

### 1. Installation

**Via Composer (Recommended):**
```bash
composer create-project guiziweb/mcp-redmine --stability=dev
```

**Via Git (For Development):**
```bash
git clone https://github.com/guiziweb/mcp-redmine.git
cd mcp-redmine
composer install
```

### 2. Configuration

> 💡 **Get your Redmine API key**: Go to Redmine → My account → API access key → Show

**Update `.env` file:**
```bash
REDMINE_URL=https://your-redmine-instance.com
REDMINE_API_KEY=your_api_key_here
```

**Start the HTTP server:**
```bash
symfony server:start
# or
php -S 127.0.0.1:8000 -t public
```

**Configure MCP Client:**

Create a `.mcp.json` file:
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

### 3. Restart Your AI Assistant

Close and restart your MCP client (Claude Desktop, Cursor, etc.).

## 🔐 Remote Access with OAuth2 (Team Sharing)

Want to share your MCP server with your team? Secure it with OAuth2 + Keycloak!

**Benefits:**
- 🌐 **Remote access**: Deploy once, use from anywhere
- 👥 **Team sharing**: Multiple users, centralized authentication
- 🔒 **Access control**: Restrict by email domain, groups, roles
- 📊 **Audit trail**: Track who does what

**Quick Setup:**
1. Start Keycloak with Docker
2. Configure realm with Dynamic Client Registration (DCR)
3. Update MCP server `.env` with Keycloak credentials
4. Clients auto-register and authenticate via browser

👉 **[Complete OAuth2 Setup Guide](docs/OAUTH_SETUP.md)**

## ✨ Features

### Available Tools

| Tool | Description | Parameters | Example Prompts |
|------|-------------|------------|-----------------|
| `redmine_list_projects` | Lists all your accessible Redmine projects with their hierarchy and IDs | None | • "Show me all my Redmine projects"<br>• "List my projects"<br>• "What projects do I have access to?" |
| `redmine_list_issues` | Lists issues from ONE specific project. Always shows the project list first and asks which project you want | • **project_id** (required): The project ID<br>• **limit** (optional): Max results (1-100, default: 25) | • "Show issues from project Mobile App"<br>• "List my tasks on project #123"<br>• "What tickets are assigned to me on project X?" |
| `redmine_get_issue_details` | Get detailed information about a specific Redmine issue by its ID. Returns comprehensive issue data including description, status, priority, assignee, dates, attachments, and more | • **issue_id** (required): The issue ID<br>• **include** (optional): Additional data to include (children, attachments, relations, changesets, journals, watchers, allowed_statuses) | • "Show me details of issue #123"<br>• "Get full information about ticket #456"<br>• "Show issue #789 with attachments and journals" |
| `redmine_list_time_entries` | Retrieves your time entries with smart filtering, totals, and work analysis (daily/weekly/project breakdowns) | • **from** (optional): Start date (YYYY-MM-DD)<br>• **to** (optional): End date (YYYY-MM-DD)<br>• **limit** (optional): Max results (1-100, default: 100)<br>• **project_id** (optional): Filter by project | • "Show my hours from August 1st to August 31st"<br>• "Show my time entries for last week"<br>• "What's my daily average this month?"<br>• "Get my hours by project" |
| `redmine_log_time` | Logs time to a specific issue. Will ask you for each parameter interactively (hours, comment, activity type) | • **issue_id** (required): The issue ID<br>• **hours** (required): Hours worked (0.1-24)<br>• **comment** (required): Work description (max 1000 chars)<br>• **activity_id** (required): Activity type ID | • "Log 2 hours to issue #123"<br>• "Add time to ticket #456"<br>• "I worked 3.5 hours on issue #789" |

### Smart Features

- **Date Intelligence**: "Show my time for last month", "August 2025 entries"
- **Smart Summaries**: Automatic totals, weekly/daily breakdowns
- **Work Analysis**: Hours per day, project breakdowns, weekly patterns
- **Caching**: Projects and activities cached for performance

## 🛠 Development

### Requirements

- PHP 8.2+
- Composer
- Access to a Redmine instance with API enabled

### Api

- **Redmine API Client**: [kbsali/redmine-api](https://github.com/kbsali/php-redmine-api) v2.8+ - A comprehensive PHP library for Redmine API

### Testing

```bash
# Run all tests
composer test

# Run specific test
vendor/bin/phpunit tests/Tools/ListProjectsToolTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage
```

### Code Quality

```bash
# Static analysis
vendor/bin/phpstan analyze

# Code style
vendor/bin/php-cs-fixer fix
```

## 🔧 Configuration Options

### Cache Settings

- **Projects**: 24 hours (rarely change)
- **Activities**:24 hours (rarely change)
- **Issues**: No cache (change frequently)
- **Time entries**: No cache (real-time data)

## 🚨 Security

- ✅ Environment-based configuration
- ✅ Validation on all inputs
- ✅ Error handling without data exposure

## 🐛 Troubleshooting

### Common Issues

1. **"Connection refused" or "Server not accessible"**
   - Verify HTTP server is running (`symfony server:start` or `php -S 127.0.0.1:8000`)
   - Check the URL in `.mcp.json` matches your server address
   - Ensure no firewall blocking the port

2. **"Authentication failed"**
   - Verify `REDMINE_URL` and `REDMINE_API_KEY` in `.env`
   - Check API key permissions in Redmine
   - Ensure API is enabled in Redmine settings

3. **"Invalid token" (with OAuth2)**
   - Verify Keycloak is running
   - Check OAuth config in `.env` (KEYCLOAK_URL, KEYCLOAK_REALM, KEYCLOAK_AUDIENCE)
   - Reconnect the MCP client to get a fresh token
   - See [OAuth2 Setup Guide](OAUTH_SETUP.md) for details

## 🔗 Related

- [Model Context Protocol](https://github.com/anthropics/mcp)
- [Claude Desktop](https://claude.ai/desktop)
- [Redmine API Documentation](https://www.redmine.org/projects/redmine/wiki/Rest_api)
