<?php

namespace App\Modules\Access\Services;

class Permissions
{
    public static function all(): array
    {
        $modules = ['customers' => 'Clientes', 'catalog' => 'Catálogo', 'quotes' => 'Presupuestos', 'costs' => 'Costos', 'purchases' => 'Compras', 'inventory' => 'Inventario', 'orders' => 'Pedidos'];
        $actions = ['view' => 'Consultar', 'create' => 'Crear', 'update' => 'Modificar', 'delete' => 'Eliminar', 'approve' => 'Aprobar', 'export' => 'Exportar'];
        $supported = ['customers' => ['view', 'create', 'update'], 'catalog' => ['view', 'create', 'update'], 'quotes' => array_keys($actions), 'costs' => ['view', 'create', 'approve'], 'purchases' => ['view', 'create', 'update', 'approve'], 'inventory' => ['view', 'update'], 'orders' => ['view', 'create', 'update', 'approve']];
        $result = [];
        foreach ($supported as $module => $list) {
            foreach ($list as $action) {
                $result["$module.$action"] = $modules[$module].' · '.$actions[$action];
            }
        }

        return $result + ['dashboard.view' => 'Dashboard · Consultar', 'orders.view-own' => 'Pedidos · Solo asignados, sin información comercial', 'users.manage' => 'Usuarios · Administrar', 'roles.manage' => 'Roles · Administrar', 'company.update' => 'Empresa · Administrar', 'audit.view' => 'Auditoría · Consultar', 'legal.manage' => 'Documentos legales · Administrar'];
    }

    public static function defaults(): array
    {
        $all = array_keys(self::all());
        $read = ['customers.view', 'catalog.view', 'quotes.view', 'quotes.export', 'inventory.view', 'orders.view', 'dashboard.view'];

        return [
            'admin' => ['Administrador', $all],
            'commercial' => ['Comercial', array_values(array_diff($all, ['orders.view-own', 'users.manage', 'roles.manage', 'company.update', 'audit.view', 'legal.manage']))],
            'reader' => ['Consulta', $read],
            'accounting' => ['Administración / contabilidad', array_merge($read, ['costs.view', 'purchases.view'])],
            'hr' => ['Recursos humanos', []],
            'technician' => ['Técnico', ['orders.view-own']],
        ];
    }
}
