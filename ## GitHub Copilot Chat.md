## GitHub Copilot Chat

- Extension Version: 0.25.1 (prod)
- VS Code: vscode/1.98.2
- OS: Windows

## Network

User Settings:
```json
  "github.copilot.advanced.debug.useElectronFetcher": true,
  "github.copilot.advanced.debug.useNodeFetcher": false,
  "github.copilot.advanced.debug.useNodeFetchFetcher": true
```

Connecting to https://api.github.com:
- DNS ipv4 Lookup: 20.27.177.116 (25 ms)
- DNS ipv6 Lookup: Error (675 ms): getaddrinfo ENOTFOUND api.github.com
- Proxy URL: None (7 ms)
- Electron fetch (configured): HTTP 200 (139 ms)
- Node.js https: HTTP 200 (230 ms)
- Node.js fetch: HTTP 200 (180 ms)
- Helix fetch: HTTP 200 (421 ms)

Connecting to https://api.individual.githubcopilot.com/_ping:
- DNS ipv4 Lookup: 140.82.112.21 (17 ms)
- DNS ipv6 Lookup: Error (19 ms): getaddrinfo ENOTFOUND api.individual.githubcopilot.com
- Proxy URL: None (50 ms)
- Electron fetch (configured): HTTP 200 (168 ms)
- Node.js https: HTTP 200 (584 ms)
- Node.js fetch: HTTP 200 (623 ms)
- Helix fetch: HTTP 200 (608 ms)

## Documentation

In corporate networks: [Troubleshooting firewall settings for GitHub Copilot](https://docs.github.com/en/copilot/troubleshooting-github-copilot/troubleshooting-firewall-settings-for-github-copilot).