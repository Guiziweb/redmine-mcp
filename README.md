# MCP Redmine Server

An MCP (Model Context Protocol) server that integrates Redmine with AI assistants like Claude Desktop, enabling natural language interaction with your Redmine instance.

## Quick Start

### 1. Installation

**Via Composer (Recommended):**
```bash
composer create-project guiziweb/mcp-redmine --stability=dev
cd mcp-redmine
```

**Via Git (For Development):**
```bash
git clone https://github.com/guiziweb/mcp-redmine.git
cd mcp-redmine
composer install
```

### 2. Get Your Redmine API Key

Go to Redmine → My account → API access key → Show

### 3. Configure MCP Client

Edit your `.mcp.json` (usually at `~/.config/claude-code/.mcp.json`):

```json
{
  "mcpServers": {
    "redmine": {
      "type": "stdio",
      "command": "/opt/homebrew/bin/php",
      "args": ["/absolute/path/to/mcp-redmine/bin/console", "mcp:serve"],
      "env": {
        "REDMINE_URL": "https://your-redmine-instance.com",
        "REDMINE_API_KEY": "your_api_key_here"
      }
    }
  }
}
```

**Important:**
- Use absolute path for PHP: `which php` to find it (usually `/opt/homebrew/bin/php` on macOS)
- Replace `/absolute/path/to/mcp-redmine` with the actual path to this project (use `pwd` in project directory)

### 4. Restart Claude Code

Quit and restart Claude Code to load the MCP server.

## Features

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

## Development

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

## Configuration Options

### Cache Settings

- **Projects**: 24 hours (rarely change)
- **Activities**:24 hours (rarely change)
- **Issues**: No cache (change frequently)
- **Time entries**: No cache (real-time data)

## Security

- Environment-based configuration
- Validation on all inputs
- Error handling without data exposure

## Troubleshooting

### Common Issues

1. **MCP server not connecting**
   - Verify the command path in `.mcp.json` is absolute (use `pwd` to get the full path)
   - Test the command manually: `php /path/to/bin/console mcp:serve`
   - Restart Claude Code after any configuration change

2. **"Authentication failed"**
   - Verify `REDMINE_URL` and `REDMINE_API_KEY` in `.mcp.json` are correct
   - Check API key permissions in Redmine (My account → API access key)
   - Ensure API is enabled in Redmine settings (Administration → Settings → API)

## Related

- [Model Context Protocol](https://github.com/anthropics/mcp)
- [Claude Desktop](https://claude.ai/desktop)
- [Redmine API Documentation](https://www.redmine.org/projects/redmine/wiki/Rest_api)
