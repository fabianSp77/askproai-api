#!/usr/bin/env node
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { Octokit } from '@octokit/rest';
import { config } from 'dotenv';

config();

// GitHub API client
const octokit = new Octokit({
  auth: process.env.GITHUB_TOKEN
});

// Create MCP server
const server = new Server(
  {
    name: 'github-mcp',
    version: '1.0.0',
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

// Tool definitions
server.setRequestHandler('tools/list', async () => ({
  tools: [
    {
      name: 'search_repositories',
      description: 'Search GitHub repositories',
      inputSchema: {
        type: 'object',
        properties: {
          query: {
            type: 'string',
            description: 'Search query',
          },
          sort: {
            type: 'string',
            description: 'Sort by: stars, forks, updated',
            enum: ['stars', 'forks', 'updated'],
          },
          per_page: {
            type: 'number',
            description: 'Results per page (max 100)',
            default: 10,
          },
        },
        required: ['query'],
      },
    },
    {
      name: 'get_repository',
      description: 'Get details about a specific repository',
      inputSchema: {
        type: 'object',
        properties: {
          owner: {
            type: 'string',
            description: 'Repository owner',
          },
          repo: {
            type: 'string',
            description: 'Repository name',
          },
        },
        required: ['owner', 'repo'],
      },
    },
    {
      name: 'list_issues',
      description: 'List issues for a repository',
      inputSchema: {
        type: 'object',
        properties: {
          owner: {
            type: 'string',
            description: 'Repository owner',
          },
          repo: {
            type: 'string',
            description: 'Repository name',
          },
          state: {
            type: 'string',
            description: 'Issue state',
            enum: ['open', 'closed', 'all'],
            default: 'open',
          },
          labels: {
            type: 'string',
            description: 'Comma-separated list of labels',
          },
        },
        required: ['owner', 'repo'],
      },
    },
    {
      name: 'create_issue',
      description: 'Create a new issue',
      inputSchema: {
        type: 'object',
        properties: {
          owner: {
            type: 'string',
            description: 'Repository owner',
          },
          repo: {
            type: 'string',
            description: 'Repository name',
          },
          title: {
            type: 'string',
            description: 'Issue title',
          },
          body: {
            type: 'string',
            description: 'Issue body',
          },
          labels: {
            type: 'array',
            items: { type: 'string' },
            description: 'Issue labels',
          },
        },
        required: ['owner', 'repo', 'title'],
      },
    },
    {
      name: 'list_pull_requests',
      description: 'List pull requests for a repository',
      inputSchema: {
        type: 'object',
        properties: {
          owner: {
            type: 'string',
            description: 'Repository owner',
          },
          repo: {
            type: 'string',
            description: 'Repository name',
          },
          state: {
            type: 'string',
            description: 'PR state',
            enum: ['open', 'closed', 'all'],
            default: 'open',
          },
        },
        required: ['owner', 'repo'],
      },
    },
    {
      name: 'get_file_contents',
      description: 'Get contents of a file in a repository',
      inputSchema: {
        type: 'object',
        properties: {
          owner: {
            type: 'string',
            description: 'Repository owner',
          },
          repo: {
            type: 'string',
            description: 'Repository name',
          },
          path: {
            type: 'string',
            description: 'File path',
          },
          ref: {
            type: 'string',
            description: 'Branch, tag, or commit',
            default: 'main',
          },
        },
        required: ['owner', 'repo', 'path'],
      },
    },
    {
      name: 'list_branches',
      description: 'List branches in a repository',
      inputSchema: {
        type: 'object',
        properties: {
          owner: {
            type: 'string',
            description: 'Repository owner',
          },
          repo: {
            type: 'string',
            description: 'Repository name',
          },
        },
        required: ['owner', 'repo'],
      },
    },
    {
      name: 'get_commit',
      description: 'Get details about a specific commit',
      inputSchema: {
        type: 'object',
        properties: {
          owner: {
            type: 'string',
            description: 'Repository owner',
          },
          repo: {
            type: 'string',
            description: 'Repository name',
          },
          ref: {
            type: 'string',
            description: 'Commit SHA',
          },
        },
        required: ['owner', 'repo', 'ref'],
      },
    },
  ],
}));

// Tool execution handler
server.setRequestHandler('tools/call', async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case 'search_repositories': {
        const { data } = await octokit.search.repos({
          q: args.query,
          sort: args.sort || 'stars',
          per_page: args.per_page || 10,
        });
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(data, null, 2),
            },
          ],
        };
      }

      case 'get_repository': {
        const { data } = await octokit.repos.get({
          owner: args.owner,
          repo: args.repo,
        });
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(data, null, 2),
            },
          ],
        };
      }

      case 'list_issues': {
        const params = {
          owner: args.owner,
          repo: args.repo,
          state: args.state || 'open',
        };
        if (args.labels) {
          params.labels = args.labels;
        }
        const { data } = await octokit.issues.listForRepo(params);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(data, null, 2),
            },
          ],
        };
      }

      case 'create_issue': {
        const { data } = await octokit.issues.create({
          owner: args.owner,
          repo: args.repo,
          title: args.title,
          body: args.body || '',
          labels: args.labels || [],
        });
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(data, null, 2),
            },
          ],
        };
      }

      case 'list_pull_requests': {
        const { data } = await octokit.pulls.list({
          owner: args.owner,
          repo: args.repo,
          state: args.state || 'open',
        });
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(data, null, 2),
            },
          ],
        };
      }

      case 'get_file_contents': {
        const { data } = await octokit.repos.getContent({
          owner: args.owner,
          repo: args.repo,
          path: args.path,
          ref: args.ref || 'main',
        });
        
        // Decode base64 content if it's a file
        if (data.content && data.encoding === 'base64') {
          const decoded = Buffer.from(data.content, 'base64').toString('utf-8');
          return {
            content: [
              {
                type: 'text',
                text: decoded,
              },
            ],
          };
        }
        
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(data, null, 2),
            },
          ],
        };
      }

      case 'list_branches': {
        const { data } = await octokit.repos.listBranches({
          owner: args.owner,
          repo: args.repo,
        });
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(data, null, 2),
            },
          ],
        };
      }

      case 'get_commit': {
        const { data } = await octokit.repos.getCommit({
          owner: args.owner,
          repo: args.repo,
          ref: args.ref,
        });
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(data, null, 2),
            },
          ],
        };
      }

      default:
        throw new Error(`Unknown tool: ${name}`);
    }
  } catch (error) {
    return {
      content: [
        {
          type: 'text',
          text: `Error: ${error.message}`,
        },
      ],
      isError: true,
    };
  }
});

// Start the server
async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error('GitHub MCP Server running on stdio');
}

main().catch((error) => {
  console.error('Fatal error:', error);
  process.exit(1);
});