# ReachLimiter

**ReachLimiter** is a simple yet powerful anti-reach plugin for PocketMine-MP that prevents players from attacking others from an unfair distance using reach hacks or modified clients.

---

## 📌 Features

- 🔒 Prevents players from hitting entities beyond a configurable max reach.
- 🎯 High-precision hit distance calculation (based on eye and body position).
- 💬 Sends real-time feedback to the attacker with exact hit distance.
- ⚙️ Configurable via `config.yml`.
- 🚀 Lightweight and optimized for performance.
- 🛡️ No false positives — smooth and fair PvP experience.

---

## 📂 Configuration

When the plugin is first loaded, it generates a `config.yml` file inside the plugin's data folder:

```yaml
max-reach: 3.84
