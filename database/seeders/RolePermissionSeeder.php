<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Permissions List
        $permissions = [
            // User & Role
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',
            // Supplier
            'supplier-list', 'supplier-create', 'supplier-edit', 'supplier-delete',
            // Material & Stock
            'material-list', 'material-create', 'material-edit', 'material-delete',
            // Product & BOM (Recipe)
            'product-list', 'product-create', 'product-edit', 'product-delete',
            'recipe-manage',
            // Purchasing
            'purchase-list', 'purchase-create', 'purchase-approve', 'purchase-receive',
            // POS
            'pos-access',
            // Reports & Dashboard
            'report-access', 'dashboard-access'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Roles based on PRD: Owner, Manager, Cashier
        $ownerRole = Role::firstOrCreate(['name' => 'Owner']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);

        // Owner: Full System Access (Termasuk Approve PO)
        $ownerRole->syncPermissions(Permission::all());

        // Manager: Master data, PO create, Goods receipt, Stock opname, Report
        $managerRole->syncPermissions([
            'dashboard-access',
            'supplier-list', 'supplier-create', 'supplier-edit',
            'material-list', 'material-create', 'material-edit',
            'product-list', 'product-create', 'product-edit', 'recipe-manage',
            'purchase-list', 'purchase-create', 'purchase-receive',
            'report-access'
        ]);

        // Cashier: POS Access only & Sales summary
        $cashierRole->syncPermissions([
            'pos-access'
        ]);

        // Assign Owner role to current admin if exists
        $admin = User::where('email', 'admin@gmail.com')->first();
        if ($admin) {
            $admin->assignRole('Owner');
        }
    }
}
