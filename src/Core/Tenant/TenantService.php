<?php

namespace Minimarcket\Core\Tenant;

use Minimarcket\Core\Database\ConnectionManager;
use PDO;

class TenantService
{
    private ConnectionManager $db;

    public function __construct(ConnectionManager $db)
    {
        $this->db = $db;
    }

    /**
     * Identifica el tenant basado en el dominio o subdominio.
     * En entorno local, usa 'default'.
     */
    public function identifyTenant(): array
    {
        // For development/MVP simplification: Always return Default Tenant
        // In real impl, checking $_SERVER['HTTP_HOST']

        // $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Parse subdomain... 

        $subdomain = 'default'; // Hardcoded for Phase 3 Step 1

        return $this->getTenantBySubdomain($subdomain);
    }

    public function getTenantBySubdomain(string $subdomain): array
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM tenants WHERE subdomain = ? LIMIT 1");
        $stmt->execute([$subdomain]);
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tenant) {
            // Fallback to default if not found (or throw Exception)
            // Try to find ID 1
            $stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = 1 LIMIT 1");
            $stmt->execute();
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$tenant) {
            throw new \Exception("No active tenant found.");
        }

        return $tenant;
    }
}
