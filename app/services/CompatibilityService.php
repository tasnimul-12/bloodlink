<?php
/**
 * BloodLink - Blood Group & Component Compatibility Engine
 * 
 * Implements standard academic/clinical rules for blood component transfusion:
 * - Red Blood Cells / Whole Blood:
 *     O- is universal donor; AB+ is universal recipient.
 * - Plasma:
 *     AB is universal plasma donor; O is universal recipient.
 * - Platelets:
 *     ABO identical preferred, followed by ABO compatible plasma.
 */

class CompatibilityService {
    // Blood Group IDs mapping:
    // 1: A+, 2: A-, 3: B+, 4: B-, 5: AB+, 6: AB-, 7: O+, 8: O-

    private static array $groupNames = [
        1 => 'A+', 2 => 'A-', 3 => 'B+', 4 => 'B-',
        5 => 'AB+', 6 => 'AB-', 7 => 'O+', 8 => 'O-'
    ];

    private static array $groupNameToId = [
        'A+' => 1, 'A-' => 2, 'B+' => 3, 'B-' => 4,
        'AB+' => 5, 'AB-' => 6, 'O+' => 7, 'O-' => 8
    ];

    /**
     * Returns an array of donor blood_group_ids compatible with a recipient's blood group
     */
    public static function getCompatibleDonorGroupIds(int $recipientGroupId, string $componentType = 'WHOLE_BLOOD'): array {
        $recipientGroup = self::$groupNames[$recipientGroupId] ?? null;
        if (!$recipientGroup) {
            return [$recipientGroupId];
        }

        if ($componentType === 'PLASMA') {
            // Plasma compatibility matrix
            return match ($recipientGroup) {
                'AB+', 'AB-' => [self::$groupNameToId['AB+'], self::$groupNameToId['AB-']],
                'A+', 'A-'   => [self::$groupNameToId['A+'], self::$groupNameToId['A-'], self::$groupNameToId['AB+'], self::$groupNameToId['AB-']],
                'B+', 'B-'   => [self::$groupNameToId['B+'], self::$groupNameToId['B-'], self::$groupNameToId['AB+'], self::$groupNameToId['AB-']],
                'O+', 'O-'   => [1, 2, 3, 4, 5, 6, 7, 8], // O can receive plasma from any group
                default      => [$recipientGroupId]
            };
        }

        // Standard Whole Blood and Red Blood Cells (RBC) / Platelets
        return match ($recipientGroup) {
            'A+'  => [self::$groupNameToId['A+'], self::$groupNameToId['A-'], self::$groupNameToId['O+'], self::$groupNameToId['O-']],
            'A-'  => [self::$groupNameToId['A-'], self::$groupNameToId['O-']],
            'B+'  => [self::$groupNameToId['B+'], self::$groupNameToId['B-'], self::$groupNameToId['O+'], self::$groupNameToId['O-']],
            'B-'  => [self::$groupNameToId['B-'], self::$groupNameToId['O-']],
            'AB+' => [1, 2, 3, 4, 5, 6, 7, 8], // AB+ is Universal Recipient for RBC/Whole Blood
            'AB-' => [self::$groupNameToId['AB-'], self::$groupNameToId['A-'], self::$groupNameToId['B-'], self::$groupNameToId['O-']],
            'O+'  => [self::$groupNameToId['O+'], self::$groupNameToId['O-']],
            'O-'  => [self::$groupNameToId['O-']], // O- can only receive O-
            default => [$recipientGroupId]
        };
    }

    public static function isCompatible(int $donorGroupId, int $recipientGroupId, string $componentType = 'WHOLE_BLOOD'): bool {
        $compatible = self::getCompatibleDonorGroupIds($recipientGroupId, $componentType);
        return in_array($donorGroupId, $compatible, true);
    }

    public static function getGroupName(int $groupId): string {
        return self::$groupNames[$groupId] ?? 'Unknown';
    }

    public static function getCompatibilityMatrix(): array {
        $matrix = [];
        foreach (self::$groupNames as $id => $name) {
            $rbcDonors = array_map(fn($dId) => self::$groupNames[$dId], self::getCompatibleDonorGroupIds($id, 'WHOLE_BLOOD'));
            $plasmaDonors = array_map(fn($dId) => self::$groupNames[$dId], self::getCompatibleDonorGroupIds($id, 'PLASMA'));
            $matrix[] = [
                'id' => $id,
                'name' => $name,
                'rbc_donors' => implode(', ', $rbcDonors),
                'plasma_donors' => implode(', ', $plasmaDonors)
            ];
        }
        return $matrix;
    }
}
