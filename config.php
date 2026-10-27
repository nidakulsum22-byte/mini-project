<?php
// ---------- Database settings (edit for your XAMPP / WAMP) ----------
const DB_HOST = 'localhost';
const DB_NAME = 'blockchain_tracker';
const DB_USER = 'root';
const DB_PASS = '';

// Proof-of-work difficulty: hash must start with this many zeros.
// 3 = fast demo, 4 = noticeably slower but "more secure".
const DIFFICULTY = 3;

const EVENT_TYPES = [
    'MANUFACTURED'      => 'Manufactured',
    'QUALITY_CHECKED'   => 'Quality checked',
    'SHIPPED'           => 'Shipped',
    'IN_TRANSIT'        => 'In transit',
    'RECEIVED_WAREHOUSE'=> 'Received at warehouse',
    'OUT_FOR_DELIVERY'  => 'Out for delivery',
    'DELIVERED'         => 'Delivered',
];

session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $ex) {
            die('Database connection failed. Did you import database.sql and check config.php? <br>' . htmlspecialchars($ex->getMessage()));
        }
    }
    return $pdo;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }

function take_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function short_hash(string $h): string { return substr($h, 0, 8) . '…' . substr($h, -6); }

require_once __DIR__ . '/Blockchain.php';
