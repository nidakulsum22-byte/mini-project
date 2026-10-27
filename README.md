# ChainTrack – Blockchain Product Tracking System (PHP + MySQL)

A mini project showing how blockchain principles (hash-linked blocks, proof of work,
tamper detection) can make a product supply chain transparent.

## How to run (XAMPP / WAMP)
1. Copy the `chaintrack` folder into `htdocs` (XAMPP) or `www` (WAMP).
2. Start **Apache** and **MySQL**.
3. Open phpMyAdmin -> Import -> choose `database.sql` -> Go.
4. Check DB user/password in `config.php` (XAMPP default: `root`, empty password).
5. Visit http://localhost/chaintrack/

Requires PHP 7.4+ (PHP 8 recommended) with the PDO MySQL extension.

## Demo flow for your viva
1. Dashboard -> register a product (e.g. `MED-1001`). This mines the genesis block.
2. Record events: Shipped -> In transit -> Received at warehouse -> Delivered.
3. "Track a product" -> enter the code -> see the verified timeline.
4. "Blockchain ledger" -> use the tamper demo on any block -> every block turns red
   from that point, and the product page shows "Tampered".

## File overview
| File | Purpose |
|---|---|
| `database.sql` | Creates DB, `products` and `blocks` tables |
| `config.php` | DB settings, difficulty, event list, helpers |
| `Blockchain.php` | Core logic: hashing, mining (proof of work), add block, validate chain |
| `index.php` | Dashboard: register product, add event |
| `track.php` | Customer view: product journey + verification badge |
| `chain.php` | Ledger explorer, chain validation, tamper demo |
| `header.php` / `footer.php` / `style.css` | Layout and styling |

## How the blockchain works
- Block hash = SHA-256 of (index, timestamp, product, event, location, handler, notes, previous hash, nonce).
- Each block stores the previous block's hash. Changing old data changes its hash,
  so the next block's `previous_hash` no longer matches.
- Proof of work: nonce is increased until the hash starts with `000` (`DIFFICULTY` in config.php).
- `validate()` recomputes everything and reports exactly which block broke and why.

## Limitations / future scope (good for the report)
- Single-server chain; a real blockchain is distributed across many nodes with consensus.
- No login yet: add roles (Manufacturer, Distributor, Retailer) with sessions and password_hash().
- Add QR codes per product, email/SMS alerts, REST API, or a smart-contract version (Ethereum/Solidity).
