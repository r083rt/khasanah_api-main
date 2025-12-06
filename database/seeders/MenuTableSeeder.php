<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

use function Symfony\Component\String\s;

class MenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->dashboard();
        $this->management();
        $this->pos();
        $this->inventory();
        $this->distribution();
        $this->production();
        $this->reporting();
    }

    private function dashboard()
    {
        $data = [
            [
                'title' => "Dashboard",
                'parent_id' => null,
                'classification' => 'home',
                'icon' => 'home',
                'url' => '/dashboard',
                'type' => 'item',
                'order_menu' => 1,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Dashboard',
                        'action' => 'dashboard.lihat'
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }
    }

    private function management()
    {
        $data = [
            [
                'title' => "User",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'person',
                'url' => '/management/users',
                'type' => 'item',
                'order_menu' => 1,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'user.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'user.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'user.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'user.hapus',
                    ]
                ]
            ],
            [
                'title' => "Menu",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'menu',
                'url' => '/management/menus',
                'type' => 'item',
                'order_menu' => 5,
                'is_displayed' => 0,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'menu.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'menu.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'menu.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'menu.hapus',
                    ]
                ]
            ],
            [
                'title' => "Role",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'admin_panel_settings',
                'url' => '/management/roles',
                'type' => 'item',
                'order_menu' => 6,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'role.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'role.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'role.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'role.hapus',
                    ]
                ]
            ],
            [
                'title' => "Pelanggan",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'groups',
                'url' => '/management/customers',
                'type' => 'item',
                'order_menu' => 3,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'customer.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'customer.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'customer.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'customer.hapus',
                    ]
                ]
            ],
            [
                'title' => "User Session",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'accessibility',
                'url' => '/management/sessions',
                'type' => 'item',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'session.lihat',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'session.hapus',
                    ]
                ]
            ],
            [
                'title' => "Jalur Pengiriman",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'linear_scale',
                'url' => '/management/shippings',
                'type' => 'item',
                'order_menu' => 7,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'pengiriman.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'pengiriman.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'pengiriman.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'pengiriman.hapus',
                    ]
                ]
            ],
            [
                'title' => "Supplier",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'agriculture',
                'url' => '/management/suppliers',
                'type' => 'item',
                'order_menu' => 8,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'supplier.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'supplier.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'supplier.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'supplier.hapus',
                    ]
                ]
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }

        $data = [
            [
                'title' => "Cabang",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'account_balance',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 4,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "Cabang",
                    'parent_id' => $menu->id,
                    'classification' => 'management',
                    'icon' => 'account_balance',
                    'url' => '/management/branches',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'branch.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'branch.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'branch.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'branch.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Wilayah",
                    'parent_id' => $menu->id,
                    'classification' => 'management',
                    'icon' => 'map',
                    'url' => '/management/territories',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'wilayah.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'wilayah.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'wilayah.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'wilayah.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Area",
                    'parent_id' => $menu->id,
                    'classification' => 'management',
                    'icon' => 'explore',
                    'url' => '/management/areas',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'area.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'area.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'area.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'area.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Diskon",
                    'parent_id' => $menu->id,
                    'classification' => 'management',
                    'icon' => 'local_offer',
                    'url' => '/management/branch-discounts',
                    'type' => 'item',
                    'order_menu' => 4,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'cabang-diskon.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'cabang-diskon.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'cabang-diskon.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'cabang-diskon.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Pengaturan PC",
                    'parent_id' => $menu->id,
                    'classification' => 'management',
                    'icon' => 'devices',
                    'url' => '/management/branches-setting',
                    'type' => 'item',
                    'order_menu' => 5,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'branch-setting.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'branch-setting.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'branch-setting.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'branch-setting.hapus',
                        ]
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }
    }

    private function inventory()
    {
        $data = [
            [
                'title' => "Master Barang",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'kitchen',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 1,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "Barang",
                    'parent_id' => $menu->id,
                    'classification' => 'inventory',
                    'icon' => 'kitchen',
                    'url' => '/inventory/products',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'barang.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'barang.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'barang.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'barang.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Jenis Barang",
                    'parent_id' => $menu->id,
                    'classification' => 'inventory',
                    'icon' => 'label',
                    'url' => '/inventory/categories',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'jenis-barang.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'jenis-barang.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'jenis-barang.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'jenis-barang.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Satuan Barang",
                    'parent_id' => $menu->id,
                    'classification' => 'inventory',
                    'icon' => 'attachment',
                    'url' => '/inventory/invetories',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'satuan-barang.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'satuan-barang.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'satuan-barang.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'satuan-barang.hapus',
                        ]
                    ]
                ]
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }

        $data = [
            [
                'title' => "Master Bahan",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'bubble_chart',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "Master Bahan",
                    'parent_id' => $menu->id,
                    'classification' => 'inventory',
                    'icon' => 'bubble_chart',
                    'url' => '/inventory/ingredients',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'bahan-resep.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'bahan-resep.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'bahan-resep.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'bahan-resep.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Satuan Bahan",
                    'parent_id' => $menu->id,
                    'classification' => 'inventory',
                    'icon' => 'attachment',
                    'url' => '/inventory/recipe-units',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'resep-unit.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'resep-unit.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'resep-unit.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'resep-unit.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Resep",
                    'parent_id' => $menu->id,
                    'classification' => 'inventory',
                    'icon' => 'assignment',
                    'url' => '/inventory/recipes',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'resep.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'resep.tambah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'resep.hapus',
                        ]
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }

        $data = [
            [
                'title' => "Barang Masuk",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'move_to_inbox',
                'url' => '/inventory/product-incomings',
                'type' => 'item',
                'order_menu' => 3,
                'is_displayed' => 0,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'barang-masuk.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'barang-masuk.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'barang-masuk.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'barang-masuk.hapus',
                    ]
                ]
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }

        $data = [
            [
                'title' => "Stok Barang",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'filter_1',
                'url' => '/inventory/product-stocks',
                'type' => 'item',
                'order_menu' => 4,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'barang-stok.lihat',
                    ]
                ]
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }

        $data = [
            [
                'title' => "Transfer Stok",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'local_shipping',
                'url' => '/inventory/tansfer-stocks',
                'type' => 'item',
                'order_menu' => 5,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'transfer-stok.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'transfer-stok.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'transfer-stok.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'transfer-stok.hapus',
                    ],
                ]
            ],
            [
                'title' => "Penyesuaian Stok",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'create',
                'url' => '/inventory/adjustment-stocks',
                'type' => 'item',
                'order_menu' => 5,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'penyesuaian-stok.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'penyesuaian-stok.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'penyesuaian-stok.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'penyesuaian-stok.hapus',
                    ],
                ]
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }

        $data = [
            [
                'title' => "Retur/ Sumbangan",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'keyboard_return',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 6,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "Retur",
                    'parent_id' => $menu->id,
                    'classification' => 'inventory',
                    'icon' => 'keyboard_return',
                    'url' => '/inventory/returns',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'retur-barang.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'retur-barang.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'retur-barang.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'retur-barang.hapus',
                        ],
                    ]
                ],
                [
                    'title' => "Sumbangan",
                    'parent_id' => $menu->id,
                    'classification' => 'inventory',
                    'icon' => 'next_plan',
                    'url' => '/inventory/donations',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'donasi-barang.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'donasi-barang.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'donasi-barang.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'donasi-barang.hapus',
                        ],
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }
    }

    private function pos()
    {
        $data = [
            [
                'title' => "Kasir",
                'parent_id' => null,
                'classification' => 'pos',
                'icon' => 'add_shopping_cart',
                'url' => '/pos/cashiers',
                'type' => 'item',
                'order_menu' => 1,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'kasir.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'kasir.tambah',
                    ]
                ]
            ],
            [
                'title' => "History Order",
                'parent_id' => null,
                'classification' => 'pos',
                'icon' => 'history_edu',
                'url' => '/pos/order-histories',
                'type' => 'item',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'history-kasir.lihat',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'history-kasir.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'history-kasir.hapus',
                    ]
                ]
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }

        $data = [
            [
                'title' => "Pesanan",
                'parent_id' => null,
                'classification' => 'pos',
                'icon' => 'assignment',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 3,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "Buat Pesanan",
                    'parent_id' => $menu->id,
                    'classification' => 'pos',
                    'icon' => 'create',
                    'url' => '/pos/orders',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'pesanan.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'pesanan.tambah',
                        ],
                    ]
                ],
                [
                    'title' => "History Pesanan",
                    'parent_id' => $menu->id,
                    'classification' => 'pos',
                    'icon' => 'assignment',
                    'url' => '/pos/history-orders',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'history-pesanan.lihat',
                        ],
                        [
                            'name' => 'Download',
                            'action' => 'history-pesanan.download',
                        ]

                    ]
                ],
                [
                    'title' => "Summary Pesanan",
                    'parent_id' => $menu->id,
                    'classification' => 'pos',
                    'icon' => 'receipt_long',
                    'url' => '/pos/summary-orders',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'summary-pesanan.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'summary-pesanan.ubah',
                        ],
                        [
                            'name' => 'Tambah Pembayaran',
                            'action' => 'summary-pesanan.pembayaran',
                        ],
                        [
                            'name' => 'Ubah Status Pesanan',
                            'action' => 'summary-pesanan.status',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'summary-pesanan.hapus',
                        ],
                    ]
                ],
                [
                    'title' => "Detil Barang Pesanan",
                    'parent_id' => $menu->id,
                    'classification' => 'pos',
                    'icon' => 'text_snippet',
                    'url' => '/pos/product-orders',
                    'type' => 'item',
                    'order_menu' => 4,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'barang-pesanan.lihat',
                        ],
                        [
                            'name' => 'Download',
                            'action' => 'barang-pesanan.download',
                        ],
                    ]
                ],
                [
                    'title' => "Hitung Bahan Pesanan",
                    'parent_id' => $menu->id,
                    'classification' => 'pos',
                    'icon' => 'history_edu',
                    'url' => '/pos/ingredient-orders',
                    'type' => 'item',
                    'order_menu' => 5,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'bahan-pesanan.lihat',
                        ],
                        [
                            'name' => 'Download',
                            'action' => 'bahan-pesanan.download',
                        ],
                    ]
                ],
                [
                    'title' => "Pesanan Pelanggan",
                    'parent_id' => $menu->id,
                    'classification' => 'pos',
                    'icon' => 'face',
                    'url' => '/pos/customer-orders',
                    'type' => 'item',
                    'order_menu' => 6,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'pelanggan-pesanan.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'pelanggan-pesanan.ubah',
                        ],
                        [
                            'name' => 'Tambah Pembayaran',
                            'action' => 'pelanggan-pesanan.pembayaran',
                        ],
                        [
                            'name' => 'Ubah Status Pesanan',
                            'action' => 'pelanggan-pesanan.status',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'pelanggan-pesanan.hapus',
                        ],
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }

        $data = [
            [
                'title' => "Input Biaya",
                'parent_id' => null,
                'classification' => 'pos',
                'icon' => 'money_off',
                'url' => '/pos/expenses',
                'type' => 'item',
                'order_menu' => 4,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'biaya.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'biaya.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'biaya.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'biaya.hapus',
                    ]
                ]
            ],
            [
                'title' => "Closing",
                'parent_id' => null,
                'classification' => 'pos',
                'icon' => 'beenhere',
                'url' => '/pos/closings',
                'type' => 'item',
                'order_menu' => 5,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'closing.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'closing.tambah',
                    ]
                ]
            ],
            [
                'title' => "Bendahara",
                'parent_id' => null,
                'classification' => 'pos',
                'icon' => 'monetization_on',
                'url' => '/pos/closing-details',
                'type' => 'item',
                'order_menu' => 6,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'bendahara.lihat',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'bendahara.ubah',
                    ]
                ]
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }
    }

    private function distribution()
    {
        $data = [
            [
                'title' => "Gudang",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => '6_ft_apart',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 1,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "Po Pesanan Product",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'kitchen',
                    'url' => '/distribution/products',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'po-pesanan-produk.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'po-pesanan-produk.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'po-pesanan-produk.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'po-pesanan-produk.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Po Pesanan Bahan",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'bubble_chart',
                    'url' => '/distribution/ingredients',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'po-pesanan-bahan.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'po-pesanan-bahan.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'po-pesanan-bahan.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'po-pesanan-bahan.hapus',
                        ]
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }

        $data = [
            [
                'title' => "PO Manual",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'rate_review',
                'url' => '/distribution/po-manuals',
                'type' => 'item',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-manual.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'po-manual.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'po-manual.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'po-manual.hapus',
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }

        $data = [
            [
                'title' => "Cetak Surat Jalan",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'print',
                'url' => '/distribution/print-sj',
                'type' => 'item',
                'order_menu' => 3,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-print-sj.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'po-print-sj.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'po-print-sj.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'po-print-sj.hapus',
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }

        $data = [
            [
                'title' => "Terima PO",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'call_received',
                'url' => '/distribution/receive-po',
                'type' => 'item',
                'order_menu' => 4,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-receive.lihat',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'po-receive.ubah',
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }

        $data = [
            [
                'title' => "Penyesuaian PO",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'compare',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 5,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "Penyesuaian Po Product",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'compare',
                    'url' => '/distribution/adjustment-po-product',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'po-adjustment-produk.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'po-adjustment-produk.ubah',
                        ]
                    ]
                ],
                [
                    'title' => "Penyesuaian Po Bahan",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'compare',
                    'url' => '/distribution/adjustment-po-ingredients',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'po-adjustment-bahan.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'po-adjustment-bahan.ubah',
                        ]
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }

        $data = [
            [
                'title' => "LIST PO",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'list_alt',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 0,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "PO MANUAL",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'list_alt',
                    'url' => '/distribution/list/po-manual',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'list-po-manual.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'list-po-manual.ubah',
                        ]
                    ]
                ],
                [
                    'title' => "PO PESANAN PRODUK",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'list_alt',
                    'url' => '/distribution/list/po-order-product',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'list-po-pesanan-produk.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'list-po-pesanan-produk.ubah',
                        ]
                    ]
                ],
                [
                    'title' => "PO PESANAN BAHAN",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'list_alt',
                    'url' => '/distribution/list/po-order-ingredient',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'list-po-pesanan-bahan.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'list-po-pesanan-bahan.ubah',
                        ]
                    ]
                ],
                [
                    'title' => "PO BROWNIES
                    ",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'list_alt',
                    'url' => '/distribution/list/po-brownies',
                    'type' => 'item',
                    'order_menu' => 4,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'list-po-bronis.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'list-po-bronis.ubah',
                        ]
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }
    }

    private function production()
    {
        $data = [
            [
                'title' => "Rencana Target Roti Manis",
                'parent_id' => null,
                'classification' => 'production',
                'icon' => 'history_edu',
                'url' => '/production/plan',
                'type' => 'item',
                'order_menu' => 1,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'produksi-rencana.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'produksi-rencana.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'produksi-rencana.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'produksi-rencana.hapus',
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }

        $data = [
            [
                'title' => "Brownies",
                'parent_id' => null,
                'classification' => 'production',
                'icon' => 'next_plan',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "Produksi Harian",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'next_plan',
                    'url' => '/production/brownies-product',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-harian.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-harian.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-harian.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-harian.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Target Penjualan",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'highlight_alt',
                    'url' => '/production/brownies-sale',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-penjualan.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-penjualan.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-penjualan.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-penjualan.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Buffer Produksi",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'cast',
                    'url' => '/production/brownies-buffer',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-buffer.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-buffer.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-buffer.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-buffer.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Report PO",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'multiline_chart',
                    'url' => '/production/brownies-report',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-laporan.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-laporan.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-laporan.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-laporan.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "PO Produksi",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'table_chart',
                    'url' => '/production/brownies-po-production',
                    'type' => 'item',
                    'order_menu' => 4,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-po.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-po.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-po.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-po.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "PO Gudang",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'add_business',
                    'url' => '/production/brownies-po-warehouse',
                    'type' => 'item',
                    'order_menu' => 5,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-gudang.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-gudang.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-gudang.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-gudang.hapus',
                        ]
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }
    }

    private function reporting()
    {
        $data = [
            [
                'title' => "Pejualan per jenis",
                'parent_id' => null,
                'classification' => 'central_report',
                'icon' => 'analytics',
                'url' => '/reporting/sale',
                'type' => 'item',
                'order_menu' => 1,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'penjualan.lihat',
                    ]
                ]
            ],
            [
                'title' => "Item masuk",
                'parent_id' => null,
                'classification' => 'central_report',
                'icon' => 'insert_chart',
                'url' => '/reporting/incoming-product',
                'type' => 'item',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'item-masuk.lihat',
                    ]
                ]
            ],
            [
                'title' => "Penyesuaian",
                'parent_id' => null,
                'classification' => 'central_report',
                'icon' => 'multiline_chart',
                'url' => '/reporting/adjustment-product',
                'type' => 'item',
                'order_menu' => 3,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'penyesuaian-produk.lihat',
                    ]
                ]
            ],
                [
                    'title' => "History Stok",
                    'parent_id' => null,
                    'classification' => 'central_report',
                    'icon' => 'bubble_chart',
                    'url' => '/reporting/stock',
                    'type' => 'item',
                    'order_menu' => 4,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'histori-stok.lihat',
                        ]
                    ]
                ]
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }
    }
}
