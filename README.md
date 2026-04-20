# ReachLimiter ??
An optimized, scalable, and highly accurate Reach violation validator for PocketMine-MP 5. Built for competitive and high-performance server environments.
## Features
- **Fail-Fast Reach Detection**: Instantaneous damage discarding reducing CPU overhead via squared math.
- **Smart Ping Profiling (Rolling Median Filter)**: Caches minor network histories to auto-prevent false positives during lag-spikes.
- **Zero Memory Leaks**: Powered by native PHP 8 `WeakMap` implementation to fully prevent string-map garbage piling.
- **Clean Code Architecture**: Full PSR-12 standard, strict types & static code analysis (PHPStan Level Max).
## Configuration
Modify `plugins/ReachLimiter/config.yml` to fit your server's needs:
- `max-reach`: Base competitive range before padding (Usually `3.15`).
- `extra-buffer`: Distance tolerance (Default: `0.45`).
- `ping-buffer-per-100ms`: Additional mathematical distance tolerance per 100ms of ping.
## Permissions
- `reachlimiter.notify`: Allows a staff member to receive violation alerts.
- `reachlimiter.bypass`: Allows a player to completely bypass distance checks.
## Installation
1. Drop the `ReachLimiter.phar` into your `/plugins/` folder.
2. Restart the server.
3. Enjoy your competitive environment perfectly secured.
## Open Source
This plugin is perfectly fitted for production logic. Developed by **[Jorgebyte](https://discord.jorgebyte.com/)**.
Licensed under **GNU General Public License v3.0**.
