<?php

if (!function_exists('pipeline_normalize_entity_type')) {

    function pipeline_normalize_entity_type(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $value = strtolower(trim($value));
        $map = [
            'mission' => 'mission',
            'missions' => 'mission',
            'opportunity' => 'opportunity',
            'opportunities' => 'opportunity',
            'lead' => 'lead',
            'leads' => 'lead',
            'campaign' => 'campaign',
            'campaigns' => 'campaign',
        ];
        return $map[$value] ?? null;
    }

    function pipeline_entity_label(string $entityType): string
    {
        switch ($entityType) {
            case 'mission':
                return 'Missions';
            case 'lead':
                return 'Leads';
            case 'campaign':
                return 'Campagnes';
            default:
                return 'Opportunités';
        }
    }

    function pipeline_table_exists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }

    function pipeline_column_exists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    }

    function pipeline_ensure_schema(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        // Create boards table
        $pdo->exec("CREATE TABLE IF NOT EXISTS pipeline_boards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type ENUM('opportunity','mission','lead','campaign') NOT NULL,
            name VARCHAR(120) NOT NULL,
            is_default TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_entity_type (entity_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure base pipeline_stages table exists before performing ALTERs
        $pdo->exec("CREATE TABLE IF NOT EXISTS pipeline_stages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            order_position INT DEFAULT 0,
            probability_default INT DEFAULT 0,
            color_code VARCHAR(24) DEFAULT '#6c757d',
            slug VARCHAR(64) DEFAULT NULL,
            entity_type ENUM('opportunity','mission','lead','campaign') NOT NULL DEFAULT 'opportunity',
            board_id INT DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_board (board_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure pipeline_stages extensions
        try {
            if (!pipeline_column_exists($pdo, 'pipeline_stages', 'entity_type')) {
                $pdo->exec("ALTER TABLE pipeline_stages ADD COLUMN entity_type ENUM('opportunity','mission','lead','campaign') NOT NULL DEFAULT 'opportunity' AFTER name");
            }
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
        }

        try {
            if (!pipeline_column_exists($pdo, 'pipeline_stages', 'slug')) {
                $pdo->exec("ALTER TABLE pipeline_stages ADD COLUMN slug VARCHAR(64) DEFAULT NULL AFTER name");
            }
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
        }

        try {
            if (!pipeline_column_exists($pdo, 'pipeline_stages', 'board_id')) {
                $pdo->exec("ALTER TABLE pipeline_stages ADD COLUMN board_id INT NULL AFTER entity_type");
                $pdo->exec("ALTER TABLE pipeline_stages ADD KEY idx_board (board_id)");
            }
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
        }

        try {
            $pdo->exec("ALTER TABLE pipeline_stages ADD UNIQUE KEY u_board_slug (board_id, slug)");
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                throw $e;
            }
        }

        // Create linking table
        $pdo->exec("CREATE TABLE IF NOT EXISTS pipeline_entity_stages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type ENUM('opportunity','mission','lead','campaign') NOT NULL,
            entity_id INT NOT NULL,
            stage_id INT NOT NULL,
            position BIGINT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_entity (entity_type, entity_id),
            KEY idx_stage (stage_id),
            CONSTRAINT fk_pipeline_stage FOREIGN KEY (stage_id) REFERENCES pipeline_stages(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        pipeline_seed_default_boards($pdo);
        pipeline_seed_default_stages($pdo);
        pipeline_update_existing_stage_metadata($pdo);
    }

    function pipeline_get_default_definitions(): array
    {
        return [
            'opportunity' => [
                'label' => 'Pipeline Opportunités',
                'stages' => [
                    ['slug' => 'prospecting',    'name' => 'Prospection',          'order' => 1, 'probability' => 5,  'color' => '#6c757d'],
                    ['slug' => 'qualification',  'name' => 'Qualification',        'order' => 2, 'probability' => 15, 'color' => '#0d6efd'],
                    ['slug' => 'needs_analysis', 'name' => 'Analyse des besoins',  'order' => 3, 'probability' => 35, 'color' => '#17a2b8'],
                    ['slug' => 'proposal',       'name' => 'Proposition',          'order' => 4, 'probability' => 55, 'color' => '#ffc107'],
                    ['slug' => 'negotiation',    'name' => 'Négociation',          'order' => 5, 'probability' => 75, 'color' => '#fd7e14'],
                    ['slug' => 'closed_won',     'name' => 'Fermé gagné',          'order' => 6, 'probability' => 100,'color' => '#28a745'],
                    ['slug' => 'closed_lost',    'name' => 'Fermé perdu',          'order' => 7, 'probability' => 0,  'color' => '#dc3545'],
                ],
            ],
            'mission' => [
                'label' => 'Pipeline Missions',
                'stages' => [
                    ['slug' => 'planned',      'name' => 'Planifiée',    'order' => 1, 'probability' => 10, 'color' => '#6f42c1'],
                    ['slug' => 'in_progress',  'name' => 'En cours',     'order' => 2, 'probability' => 40, 'color' => '#0dcaf0'],
                    ['slug' => 'awaiting',     'name' => 'En attente',   'order' => 3, 'probability' => 55, 'color' => '#20c997'],
                    ['slug' => 'completed',    'name' => 'Terminée',     'order' => 4, 'probability' => 95, 'color' => '#198754'],
                    ['slug' => 'cancelled',    'name' => 'Annulée',      'order' => 5, 'probability' => 0,  'color' => '#dc3545'],
                ],
            ],
            'lead' => [
                'label' => 'Pipeline Leads',
                'stages' => [
                    ['slug' => 'lead',        'name' => 'Nouveau',      'order' => 1, 'probability' => 5,  'color' => '#0d6efd'],
                    ['slug' => 'contacted',   'name' => 'Contacté',     'order' => 2, 'probability' => 20, 'color' => '#6c757d'],
                    ['slug' => 'qualified',   'name' => 'Qualifié',     'order' => 3, 'probability' => 60, 'color' => '#198754'],
                    ['slug' => 'proposal',    'name' => 'Proposition',  'order' => 4, 'probability' => 75, 'color' => '#ffc107'],
                    ['slug' => 'converted',   'name' => 'Converti',     'order' => 5, 'probability' => 95, 'color' => '#20c997'],
                    ['slug' => 'unqualified', 'name' => 'Perdu',        'order' => 6, 'probability' => 0,  'color' => '#dc3545'],
                ],
            ],
            'campaign' => [
                'label' => 'Pipeline Campagnes',
                'stages' => [
                    ['slug' => 'draft',     'name' => 'Brouillon',    'order' => 1, 'probability' => 5,  'color' => '#6c757d'],
                    ['slug' => 'planning',  'name' => 'Planification','order' => 2, 'probability' => 20, 'color' => '#0d6efd'],
                    ['slug' => 'active',    'name' => 'Diffusion',    'order' => 3, 'probability' => 60, 'color' => '#20c997'],
                    ['slug' => 'analysis',  'name' => 'Analyse',      'order' => 4, 'probability' => 80, 'color' => '#ffc107'],
                    ['slug' => 'archived',  'name' => 'Clôturée',     'order' => 5, 'probability' => 0,  'color' => '#dc3545'],
                ],
            ],
        ];
    }

    function pipeline_seed_default_boards(PDO $pdo): void
    {
        $definitions = pipeline_get_default_definitions();
        foreach ($definitions as $entityType => $def) {
            pipeline_get_or_create_board($pdo, $entityType, $def['label']);
        }
    }

    function pipeline_get_or_create_board(PDO $pdo, string $entityType, string $label): int
    {
        $stmt = $pdo->prepare("SELECT id FROM pipeline_boards WHERE entity_type = ? LIMIT 1");
        $stmt->execute([$entityType]);
        $boardId = $stmt->fetchColumn();
        if ($boardId) {
            return (int)$boardId;
        }
        $stmt = $pdo->prepare("INSERT INTO pipeline_boards (entity_type, name, is_default) VALUES (?, ?, 1)");
        $stmt->execute([$entityType, $label]);
        return (int)$pdo->lastInsertId();
    }

    function pipeline_seed_default_stages(PDO $pdo): void
    {
        $definitions = pipeline_get_default_definitions();
        foreach ($definitions as $entityType => $def) {
            $boardId = pipeline_get_or_create_board($pdo, $entityType, $def['label']);
            foreach ($def['stages'] as $stage) {
                pipeline_ensure_stage($pdo, $boardId, $entityType, $stage);
            }
        }
    }

    function pipeline_ensure_stage(PDO $pdo, int $boardId, string $entityType, array $stageDef): int
    {
        $slug = $stageDef['slug'];
        $name = $stageDef['name'];
        $order = (int)($stageDef['order'] ?? 0);
        $prob = (int)($stageDef['probability'] ?? 0);
        $color = $stageDef['color'] ?? '#6c757d';

        $stmt = $pdo->prepare("SELECT id FROM pipeline_stages WHERE board_id = :board AND slug = :slug LIMIT 1");
        $stmt->execute([':board' => $boardId, ':slug' => $slug]);
        $id = $stmt->fetchColumn();
        if ($id) {
            $upd = $pdo->prepare("UPDATE pipeline_stages SET name = :name, order_position = :order, probability_default = :prob, color_code = :color, entity_type = :entity, is_active = 1 WHERE id = :id");
            $upd->execute([
                ':name' => $name,
                ':order' => $order,
                ':prob' => $prob,
                ':color' => $color,
                ':entity' => $entityType,
                ':id' => $id,
            ]);
            return (int)$id;
        }

        $stmt = $pdo->prepare("SELECT id FROM pipeline_stages WHERE board_id = :board AND name = :name LIMIT 1");
        $stmt->execute([':board' => $boardId, ':name' => $name]);
        $id = $stmt->fetchColumn();
        if ($id) {
            $upd = $pdo->prepare("UPDATE pipeline_stages SET slug = :slug, order_position = :order, probability_default = :prob, color_code = :color, entity_type = :entity, is_active = 1 WHERE id = :id");
            $upd->execute([
                ':slug' => $slug,
                ':order' => $order,
                ':prob' => $prob,
                ':color' => $color,
                ':entity' => $entityType,
                ':id' => $id,
            ]);
            return (int)$id;
        }

        $ins = $pdo->prepare("INSERT INTO pipeline_stages (name, slug, order_position, probability_default, color_code, entity_type, board_id, is_active, created_at) VALUES (:name, :slug, :order, :prob, :color, :entity, :board, 1, NOW())");
        $ins->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':order' => $order,
            ':prob' => $prob,
            ':color' => $color,
            ':entity' => $entityType,
            ':board' => $boardId,
        ]);
        return (int)$pdo->lastInsertId();
    }

    function pipeline_update_existing_stage_metadata(PDO $pdo): void
    {
        $definitions = pipeline_get_default_definitions();
        foreach ($definitions as $entityType => $def) {
            $boardId = pipeline_get_or_create_board($pdo, $entityType, $def['label']);
            foreach ($def['stages'] as $stage) {
                pipeline_ensure_stage($pdo, $boardId, $entityType, $stage);
            }
        }
    }

    function pipeline_get_stage_map(PDO $pdo, int $boardId): array
    {
        $stmt = $pdo->prepare("SELECT id, slug, name, probability_default, color_code FROM pipeline_stages WHERE board_id = :board AND is_active = 1 ORDER BY order_position, id");
        $stmt->execute([':board' => $boardId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $row) {
            $map[$row['slug']] = $row;
            $map[$row['slug']]['id'] = (int)$row['id'];
        }
        return $map;
    }

    function pipeline_get_board_id(PDO $pdo, string $entityType): int
    {
        $definitions = pipeline_get_default_definitions();
        $label = $definitions[$entityType]['label'] ?? ucfirst($entityType);
        return pipeline_get_or_create_board($pdo, $entityType, $label);
    }

    function pipeline_map_slug_from_entity(string $entityType, array $row): string
    {
        switch ($entityType) {
            case 'opportunity':
                $stage = strtolower((string)($row['stage'] ?? ''));
                $allowed = ['prospecting','qualification','needs_analysis','proposal','negotiation','closed_won','closed_lost'];
                return in_array($stage, $allowed, true) ? $stage : 'prospecting';
            case 'mission':
                $status = strtolower((string)($row['status_name'] ?? ''));
                if (strpos($status, 'cours') !== false) return 'in_progress';
                if (strpos($status, 'attente') !== false) return 'awaiting';
                if (strpos($status, 'termin') !== false) return 'completed';
                if (strpos($status, 'annul') !== false) return 'cancelled';
                return 'planned';
            case 'lead':
                $stage = strtolower((string)($row['stage'] ?? ''));
                if ($stage === 'qualified') return 'qualified';
                if ($stage === 'contacted') return 'contacted';
                if ($stage === 'proposal') return 'proposal';
                if ($stage === 'converted') return 'converted';
                if ($stage === 'unqualified') return 'unqualified';
                return 'lead';
            case 'campaign':
                $status = strtolower((string)($row['status'] ?? ''));
                if ($status === 'active' || $status === 'running') return 'active';
                if ($status === 'scheduled') return 'planning';
                if ($status === 'planning') return 'planning';
                if ($status === 'analysis') return 'analysis';
                if ($status === 'archived' || $status === 'completed' || $status === 'closed') return 'archived';
                if ($status === 'paused') return 'planning';
                return 'draft';
        }
        return 'prospecting';
    }

    function pipeline_assign_missing_entities(PDO $pdo, string $entityType, int $boardId, ?int $customerId = null, ?int $userId = null): void
    {
        $stageMap = pipeline_get_stage_map($pdo, $boardId);
        if (!$stageMap) {
            return;
        }
        $defaultStage = reset($stageMap);
        $defaultStageId = (int)$defaultStage['id'];

        $existingMap = [];
        $existingStmt = $pdo->prepare("SELECT entity_id, stage_id FROM pipeline_entity_stages WHERE entity_type = :entity");
        $existingStmt->execute([':entity' => $entityType]);
        while ($existingRow = $existingStmt->fetch(PDO::FETCH_ASSOC)) {
            $existingMap[(int)$existingRow['entity_id']] = (int)$existingRow['stage_id'];
        }

        switch ($entityType) {
            case 'opportunity':
                $sql = "SELECT o.id, o.stage, o.created_at FROM opportunities o";
                $params = [];
                if ($customerId) {
                    $sql .= " LEFT JOIN companies co ON co.id = o.company_id WHERE (o.customer_id = :customer_id OR co.customer_id = :customer_id)";
                    $params[':customer_id'] = $customerId;
                } elseif ($userId) {
                    $sql .= " WHERE o.assigned_to = :user_id";
                    $params[':user_id'] = $userId;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            case 'mission':
                $sql = "SELECT m.id, m.created_at, s.name AS status_name FROM missions m LEFT JOIN statuses s ON s.id = m.status_id";
                $params = [];
                if ($customerId) {
                    $sql .= " INNER JOIN folders f ON f.id = m.folder_id INNER JOIN companies co ON co.id = f.company_id WHERE co.customer_id = :customer_id";
                    $params[':customer_id'] = $customerId;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            case 'lead':
                $sql = "SELECT c.id, c.stage, c.created_at FROM leads c WHERE c.stage IS NOT NULL";
                $params = [];
                if ($customerId) {
                    $sql .= " AND (c.customer_id = :customer_id OR c.assigned_to = :user_id)";
                    $params[':customer_id'] = $customerId;
                    $params[':user_id'] = $userId;
                } elseif ($userId) {
                    $sql .= " AND c.assigned_to = :user_id";
                    $params[':user_id'] = $userId;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            case 'campaign':
                $sql = "SELECT c.id, c.status, c.created_at FROM campaigns c";
                $params = [];
                if ($customerId) {
                    $sql .= " WHERE c.customer_id = :customer_id";
                    $params[':customer_id'] = $customerId;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            default:
                return;
        }

        $insertStmt = $pdo->prepare("INSERT IGNORE INTO pipeline_entity_stages (entity_type, entity_id, stage_id, position, updated_at)
                VALUES (:entity_type, :entity_id, :stage_id, :position, NOW())");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slug = pipeline_map_slug_from_entity($entityType, $row);
            $stageId = $stageMap[$slug]['id'] ?? $defaultStageId;
            $position = isset($row['created_at']) ? strtotime((string)$row['created_at']) : time();
            $entityId = (int)$row['id'];
            if (isset($existingMap[$entityId]) && $existingMap[$entityId] > 0) {
                continue;
            }
            $insertStmt->execute([
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':stage_id' => $stageId,
                ':position' => $position ?: time(),
            ]);
            $existingMap[$entityId] = $stageId;
        }
    }

    function pipeline_get_board(PDO $pdo, string $entityType, ?int $customerId = null, ?int $userId = null): array
    {
        pipeline_ensure_schema($pdo);
        $boardId = pipeline_get_board_id($pdo, $entityType);
        pipeline_assign_missing_entities($pdo, $entityType, $boardId, $customerId, $userId);

        $stagesStmt = $pdo->prepare("SELECT id, name, slug, color_code, order_position, probability_default
            FROM pipeline_stages
            WHERE board_id = :board AND is_active = 1
            ORDER BY order_position, id");
        $stagesStmt->execute([':board' => $boardId]);
        $stages = $stagesStmt->fetchAll(PDO::FETCH_ASSOC);

        $stageIndex = [];
        foreach ($stages as &$stage) {
            $stage['id'] = (int)$stage['id'];
            $stage['probability_default'] = (int)$stage['probability_default'];
            $stage['items'] = [];
            $stage['count'] = 0;
            $stage['total_amount'] = 0.0;
            $stageIndex[$stage['id']] = &$stage;
        }
        unset($stage);

        $items = pipeline_fetch_items($pdo, $entityType, $boardId, $customerId, $userId);
        foreach ($items as $item) {
            $sid = $item['stage_id'];
            if (!isset($stageIndex[$sid])) {
                continue;
            }
            $stageIndex[$sid]['items'][] = $item;
            $stageIndex[$sid]['count']++;
            if (isset($item['amount'])) {
                $stageIndex[$sid]['total_amount'] += (float)$item['amount'];
            }
        }

        return [
            'board' => [
                'id' => $boardId,
                'entity_type' => $entityType,
                'label' => pipeline_entity_label($entityType),
            ],
            'stages' => array_values($stages),
        ];
    }

    function pipeline_fetch_items(PDO $pdo, string $entityType, int $boardId, ?int $customerId = null, ?int $userId = null): array
    {
        $stageMap = pipeline_get_stage_map($pdo, $boardId);
        if (!$stageMap) {
            return [];
        }
        $stageIdIndex = [];
        foreach ($stageMap as $stage) {
            $stageIdIndex[(int)$stage['id']] = $stage;
        }
        $defaultStageId = (int)reset($stageMap)['id'];

        switch ($entityType) {
            case 'opportunity':
                $sql = "SELECT o.id, o.title, o.amount, o.probability, o.stage, o.expected_close_date, o.updated_at, o.created_at,
                            co.name AS company_name,
                            pes.stage_id, pes.position
                        FROM opportunities o
                        LEFT JOIN companies co ON co.id = o.company_id
                        LEFT JOIN pipeline_entity_stages pes ON pes.entity_type = 'opportunity' AND pes.entity_id = o.id";
                $params = [];
                $conditions = [];
                if ($customerId) {
                    $conditions[] = '(o.customer_id = :customer_id OR co.customer_id = :customer_id)';
                    $params[':customer_id'] = $customerId;
                } elseif ($userId) {
                    $conditions[] = 'o.assigned_to = :user_id';
                    $params[':user_id'] = $userId;
                }
                if ($conditions) {
                    $sql .= ' WHERE ' . implode(' AND ', $conditions);
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            case 'mission':
                $sql = "SELECT m.id, m.name, m.project, m.datetime, m.updated_at, m.created_at,
                            s.name AS status_name,
                            pes.stage_id, pes.position
                        FROM missions m
                        LEFT JOIN statuses s ON s.id = m.status_id";
                $params = [];
                $conditions = [];
                if ($customerId) {
                    $sql .= "
                        INNER JOIN folders f ON f.id = m.folder_id
                        INNER JOIN companies co ON co.id = f.company_id";
                    $conditions[] = 'co.customer_id = :customer_id';
                    $params[':customer_id'] = $customerId;
                }
                $sql .= "
                        LEFT JOIN pipeline_entity_stages pes ON pes.entity_type = 'mission' AND pes.entity_id = m.id";
                if ($conditions) {
                    $sql .= ' WHERE ' . implode(' AND ', $conditions);
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            case 'lead':
                $sql = "SELECT c.id, c.first_name, c.last_name, c.email, c.company_id, c.stage, c.ai_score, c.created_at,
                            co.name AS company_name,
                            pes.stage_id, pes.position
                        FROM leads c
                        LEFT JOIN companies co ON co.id = c.company_id
                        LEFT JOIN pipeline_entity_stages pes ON pes.entity_type = 'lead' AND pes.entity_id = c.id";
                $params = [];
                $conditions = ['c.stage IS NOT NULL'];
                if ($customerId) {
                    $conditions[] = '(c.customer_id = :customer_id OR c.assigned_to = :user_id)';
                    $params[':customer_id'] = $customerId;
                    $params[':user_id'] = $userId;
                } elseif ($userId) {
                    $conditions[] = 'c.assigned_to = :user_id';
                    $params[':user_id'] = $userId;
                }
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            case 'campaign':
                $sql = "SELECT c.id, c.name, c.status, c.type, c.scheduled_at, c.created_at,
                            pes.stage_id, pes.position
                        FROM campaigns c
                        LEFT JOIN pipeline_entity_stages pes ON pes.entity_type = 'campaign' AND pes.entity_id = c.id";
                $params = [];
                $conditions = [];
                if ($customerId) {
                    $conditions[] = 'c.customer_id = :customer_id';
                    $params[':customer_id'] = $customerId;
                }
                if ($conditions) {
                    $sql .= ' WHERE ' . implode(' AND ', $conditions);
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            default:
                return [];
        }

        $items = [];
        $fixStmt = $pdo->prepare("INSERT INTO pipeline_entity_stages (entity_type, entity_id, stage_id, position, updated_at)
            VALUES (:entity_type, :entity_id, :stage_id, :position, NOW())
            ON DUPLICATE KEY UPDATE stage_id = VALUES(stage_id)");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stageId = (int)($row['stage_id'] ?? 0);
            $position = (int)($row['position'] ?? 0);
            $entityId = (int)$row['id'];

            if ($entityType === 'campaign') {
                $slugFromStatus = pipeline_map_slug_from_entity($entityType, $row);
                $expectedStageId = isset($stageMap[$slugFromStatus]['id']) ? (int)$stageMap[$slugFromStatus]['id'] : 0;
                if ($expectedStageId > 0 && $stageId !== $expectedStageId) {
                    $stageId = $expectedStageId;
                    $fixStmt->execute([
                        ':entity_type' => $entityType,
                        ':entity_id' => $entityId,
                        ':stage_id' => $stageId,
                        ':position' => $position ?: time(),
                    ]);
                }
            }

            if ($stageId <= 0 || !isset($stageIdIndex[$stageId])) {
                $stageId = $defaultStageId;
                $fixStmt->execute([
                    ':entity_type' => $entityType,
                    ':entity_id' => $entityId,
                    ':stage_id' => $stageId,
                    ':position' => $position ?: time(),
                ]);
            }

            $base = [
                'id' => $entityId,
                'stage_id' => $stageId,
                'position' => $position,
            ];

            if ($entityType === 'opportunity') {
                $base['title'] = $row['title'] ?? 'Opportunité #' . $row['id'];
                $base['subtitle'] = $row['company_name'] ?? '';
                $base['amount'] = $row['amount'] !== null ? (float)$row['amount'] : null;
                $base['probability'] = $row['probability'] !== null ? (int)$row['probability'] : null;
                $base['meta'] = $row['expected_close_date'] ?? null;
                $base['url'] = 'opportunities-edit.php?id=' . (int)$row['id'];
            } elseif ($entityType === 'mission') {
                $base['title'] = $row['name'] ?? ('Mission #' . $row['id']);
                $base['subtitle'] = $row['project'] ?? '';
                $base['meta'] = $row['datetime'] ?? $row['updated_at'] ?? $row['created_at'];
                $base['url'] = 'mission_view.php?id=' . (int)$row['id'];
                $base['status'] = $row['status_name'] ?? '';
            } elseif ($entityType === 'lead') {
                $base['title'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ('Lead #' . $row['id']);
                $base['subtitle'] = $row['email'] ?? '';
                $base['meta'] = $row['company_name'] ?? '';
                $base['score'] = $row['ai_score'] !== null ? (int)$row['ai_score'] : null;
                $base['status'] = $row['status'] ?? '';
                $base['url'] = 'contacts-edit.php?id=' . (int)$row['id'];
            } elseif ($entityType === 'campaign') {
                $base['title'] = $row['name'] ?? ('Campagne #' . $row['id']);
                $base['subtitle'] = $row['type'] ?? '';
                $base['meta'] = $row['scheduled_at'] ?? $row['created_at'];
                $base['status'] = $row['status'] ?? '';
                $base['url'] = 'campaigns-edit.php?id=' . (int)$row['id'];
            }

            $items[] = $base;
        }
        return $items;
    }

    function pipeline_get_stage_by_id(PDO $pdo, int $stageId): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM pipeline_stages WHERE id = ? LIMIT 1");
        $stmt->execute([$stageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['id'] = (int)$row['id'];
        $row['probability_default'] = (int)($row['probability_default'] ?? 0);
        return $row;
    }

    function pipeline_get_stage_by_slug(PDO $pdo, string $entityType, string $slug): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM pipeline_stages WHERE entity_type = :entity AND slug = :slug LIMIT 1");
        $stmt->execute([':entity' => $entityType, ':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['id'] = (int)$row['id'];
        $row['probability_default'] = (int)($row['probability_default'] ?? 0);
        return $row;
    }

    function pipeline_entity_belongs_to_customer(PDO $pdo, string $entityType, int $entityId, int $customerId, ?int $userId = null): bool
    {
        switch ($entityType) {
            case 'opportunity':
                $sql = "SELECT 1 FROM opportunities o LEFT JOIN companies co ON co.id = o.company_id WHERE o.id = :id AND (o.customer_id = :customer_id OR co.customer_id = :customer_id) LIMIT 1";
                break;
            case 'mission':
                $sql = "SELECT 1 FROM missions m INNER JOIN folders f ON f.id = m.folder_id INNER JOIN companies co ON co.id = f.company_id WHERE m.id = :id AND co.customer_id = :customer_id LIMIT 1";
                break;
            case 'lead':
                $sql = "SELECT 1 FROM leads c WHERE c.id = :id AND (c.customer_id = :customer_id OR c.assigned_to = :user_id) LIMIT 1";
                break;
            case 'campaign':
                $sql = "SELECT 1 FROM campaigns c WHERE c.id = :id AND c.customer_id = :customer_id LIMIT 1";
                break;
            default:
                return false;
        }
        $stmt = $pdo->prepare($sql);
        $params = [':id' => $entityId, ':customer_id' => $customerId];
        if ($entityType === 'lead' && $userId) {
            $params[':user_id'] = $userId;
        }
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    function pipeline_move_item(PDO $pdo, string $entityType, int $entityId, int $stageId, ?int $position = null, ?int $userId = null, ?int $customerId = null): array
    {
        pipeline_ensure_schema($pdo);
        $stage = pipeline_get_stage_by_id($pdo, $stageId);
        if (!$stage) {
            throw new RuntimeException('Stage introuvable');
        }
        if ($stage['entity_type'] !== $entityType) {
            error_log("PIPELINE ERROR - Stage entity_type mismatch: stage_id=$stageId, stage[entity_type]={$stage['entity_type']}, expected entityType=$entityType");
            throw new RuntimeException('Le stage ne correspond pas au type d\'entité (stage: ' . $stage['entity_type'] . ', attendu: ' . $entityType . ')');
        }

        if ($customerId) {
            $hasAccess = pipeline_entity_belongs_to_customer($pdo, $entityType, $entityId, $customerId, $userId);
            if (!$hasAccess) {
                throw new RuntimeException('Accès interdit pour ce client');
            }
        }

        $positionValue = $position ?? time();

        $stmt = $pdo->prepare("INSERT INTO pipeline_entity_stages (entity_type, entity_id, stage_id, position, updated_at)
            VALUES (:entity_type, :entity_id, :stage_id, :position, NOW())
            ON DUPLICATE KEY UPDATE stage_id = VALUES(stage_id), position = VALUES(position), updated_at = NOW()");
        $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':stage_id' => $stageId,
            ':position' => $positionValue,
        ]);

        pipeline_sync_entity_after_move($pdo, $entityType, $entityId, $stage, $userId, $customerId);

        return [
            'entity_id' => $entityId,
            'stage' => $stage,
        ];
    }

    function pipeline_sync_entity_after_move(PDO $pdo, string $entityType, int $entityId, array $stage, ?int $userId = null, ?int $customerId = null): void
    {
        $slug = $stage['slug'] ?? '';
        switch ($entityType) {
            case 'opportunity':
                $probability = $stage['probability_default'] ?? null;
                $params = [
                    ':stage' => $slug,
                    ':prob' => $probability,
                    ':id' => $entityId,
                ];
                if ($customerId) {
                    $params[':customer_id'] = $customerId;
                }
                if ($slug === 'closed_won') {
                    $sql = "UPDATE opportunities o
                        LEFT JOIN companies co ON co.id = o.company_id
                        SET o.stage = :stage, o.probability = :prob, o.actual_close_date = NOW(), o.updated_at = NOW()
                        WHERE o.id = :id";
                } elseif ($slug === 'closed_lost') {
                    $sql = "UPDATE opportunities o
                        LEFT JOIN companies co ON co.id = o.company_id
                        SET o.stage = :stage, o.probability = :prob, o.updated_at = NOW()
                        WHERE o.id = :id";
                } else {
                    $sql = "UPDATE opportunities o
                        LEFT JOIN companies co ON co.id = o.company_id
                        SET o.stage = :stage, o.probability = IF(:prob IS NULL, o.probability, :prob), o.updated_at = NOW()
                        WHERE o.id = :id";
                }
                if ($customerId) {
                    $sql .= " AND (o.customer_id = :customer_id OR co.customer_id = :customer_id)";
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            case 'mission':
                $sql = "UPDATE missions m
                    LEFT JOIN folders f ON f.id = m.folder_id
                    LEFT JOIN companies co ON co.id = f.company_id
                    SET m.updated_at = NOW()
                    WHERE m.id = :id";
                $params = [':id' => $entityId];
                if ($customerId) {
                    $sql .= " AND co.customer_id = :customer_id";
                    $params[':customer_id'] = $customerId;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            case 'lead':
                $stageValue = match ($slug) {
                    'qualified' => 'qualified',
                    'contacted' => 'contacted',
                    'proposal' => 'proposal',
                    'converted' => 'converted',
                    'unqualified' => 'unqualified',
                    default => 'lead',
                };
                $sql = "UPDATE leads SET stage = :stage, updated_at = NOW() WHERE id = :id";
                $params = [':stage' => $stageValue, ':id' => $entityId];
                if ($customerId) {
                    $sql .= " AND (customer_id = :customer_id OR assigned_to = :user_id)";
                    $params[':customer_id'] = $customerId;
                    $params[':user_id'] = $userId;
                } elseif ($userId) {
                    $sql .= " AND assigned_to = :user_id";
                    $params[':user_id'] = $userId;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
            case 'campaign':
                $statusValue = match ($slug) {
                    'active' => 'active',
                    'planning' => 'scheduled',
                    'analysis' => 'analysis',
                    'archived' => 'archived',
                    default => 'draft',
                };
                $sql = "UPDATE campaigns SET status = :status WHERE id = :id";
                $params = [':status' => $statusValue, ':id' => $entityId];
                if ($customerId) {
                    $sql .= " AND customer_id = :customer_id";
                    $params[':customer_id'] = $customerId;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
        }
    }
}