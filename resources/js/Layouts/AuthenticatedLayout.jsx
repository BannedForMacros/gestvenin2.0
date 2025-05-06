/* resources/js/Layouts/AuthenticatedLayout.jsx */
import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
  /* heroicons usados … */ HomeIcon, UserPlusIcon, ShoppingBagIcon,
  CurrencyDollarIcon, ChartBarIcon, UsersIcon, TruckIcon,
  ClipboardDocumentListIcon, FolderIcon, ChevronDownIcon, ChevronUpIcon,
  CubeIcon, TagIcon, ScaleIcon, BuildingStorefrontIcon, ClipboardIcon,
  ArrowDownCircleIcon, ArrowUpCircleIcon, ClockIcon, ShieldExclamationIcon,
  ArchiveBoxIcon, FolderOpenIcon, ListBulletIcon, FunnelIcon,
  ArrowRightOnRectangleIcon, UserCircleIcon, Bars3Icon, BellIcon,
} from '@heroicons/react/24/outline';

/* Helpers ---------------------------------------------------- */
const hasRole     = (roles, role)      => roles.includes(role);
const hasAnyRole  = (roles, allowed)   => allowed.length === 0 || allowed.some(r => roles.includes(r));
const navItemCls  = (pattern, active)  =>
  `${active ? 'bg-white text-blue-700 ring ring-blue-600 shadow' : 'text-white hover:bg-white/10'} 
   flex items-center gap-3 rounded py-2 px-2 transition`;

export default function AuthenticatedLayout({ children }) {
  const { auth } = usePage().props;
  const roles    = auth.roles ?? [];              //  ←  Array de roles
  const is       = (...r) => hasAnyRole(roles, r);

  /* UI state */
  const [sidebarOpen, setSidebarOpen]   = useState(true);
  const [mobileOpen,  setMobileOpen]    = useState(false);
  const [userDD,      setUserDD]        = useState(false);
  const [prodOpen,    setProdOpen]      = useState(false);
  const [almOpen,     setAlmOpen]       = useState(false);
  const [gastoOpen,   setGastoOpen]     = useState(false);

  /* Item factory */
  const Item = ({ href, icon, label, pattern = href, roles: allow = [] }) =>
    is(...allow) && (
      <li>
        <Link href={route(href)} className={navItemCls(pattern, route().current(pattern))}>
          {icon}
          {sidebarOpen && <span>{label}</span>}
        </Link>
      </li>
    );

  /* Sub-menú */
  const SubMenu = ({ icon, label, open, setOpen, children, roles: allow = [] }) =>
    is(...allow) && (
      <li>
        <button onClick={() => setOpen(!open)} className={navItemCls(null, false)}>
          {icon}
          {sidebarOpen && <span className="flex-1">{label}</span>}
          {sidebarOpen && (open ? <ChevronUpIcon className="h-4 w-4" /> : <ChevronDownIcon className="h-4 w-4" />)}
        </button>
        {open && sidebarOpen && (
          <ul className="ml-2 mt-1 space-y-1 border-l-2 border-white/20 pl-4">
            {children}
          </ul>
        )}
      </li>
    );

  /* --------------------------------- RENDER */
  return (
    <div className="flex h-screen overflow-hidden bg-gray-100">

      {/* =========== SIDEBAR DESKTOP =========== */}
      <aside className={`hidden md:flex flex-col bg-gradient-to-br from-[#0055D4] to-[#0070F3] transition-all ${sidebarOpen ? 'w-64' : 'w-20'}`}>
        <div className="flex items-center justify-between p-4 text-white">
          <span className="font-bold text-lg">{sidebarOpen ? 'GESTVENIN' : 'GV'}</span>
          <button onClick={() => setSidebarOpen(!sidebarOpen)} className="bg-blue-600 hover:bg-blue-800 px-2 py-1 rounded text-sm">{sidebarOpen ? '<' : '>'}</button>
        </div>

        <nav className="flex-1 overflow-y-auto">
          <ul className="space-y-1 px-2">

            {/* Ítems sueltos */}
            <Item href="dashboard"                label="Inicio"             icon={<HomeIcon className="h-6 w-6" />}                       roles={[]} />
            <Item href="users.index"              label="Gestionar Usuarios" icon={<UserPlusIcon className="h-6 w-6" />}                  roles={['soporte']} />
            <Item href="productos_ventas.index"   label="Productos Ventas"   icon={<ShoppingBagIcon className="h-6 w-6" />}              roles={['soporte']} />
            <Item href="gastos_ventas.index"      label="Gastos de Ventas"   icon={<CurrencyDollarIcon className="h-6 w-6" />}           roles={['admin','cajera']} />
            <Item href="reportes.index"           label="Reportes"           icon={<ChartBarIcon className="h-6 w-6" />}                 roles={['dueño']} />
            <Item href="gastos.index"             label="Gastos"             icon={<CurrencyDollarIcon className="h-6 w-6" />}           roles={['admin','logistica','dueño']} />
            <Item href="ventas.index"             label="Ventas"             icon={<UsersIcon className="h-6 w-6" />}                    roles={['admin','cajera']} />
            <Item href="deliveries.index"         label="Deliveries"         icon={<TruckIcon className="h-6 w-6" />}                    roles={['admin','dueño','cajera','repartidor']} />
            <Item href="cierres.index"            label="Cierre de Caja"     icon={<ClipboardDocumentListIcon className="h-6 w-6" />}    roles={['admin','dueño']} />

            {/* Productos A. */}
            <SubMenu icon={<FolderIcon className="h-6 w-6" />} label="Productos A." open={prodOpen} setOpen={setProdOpen} roles={['soporte']}>
              <Item href="productos_almacen.index" label="Productos Almacén"   icon={<CubeIcon className="h-5 w-5" />} roles={['soporte']} />
              <Item href="categorias.index"        label="Categorías"          icon={<TagIcon className="h-5 w-5" />}  roles={['soporte']} />
              <Item href="unidades_medida.index"   label="Unidades Medida"     icon={<ScaleIcon className="h-5 w-5" />} roles={['soporte']} />
              <Item href="proveedores.index"       label="Proveedores"         icon={<BuildingStorefrontIcon className="h-5 w-5" />} roles={['soporte']} />
            </SubMenu>

            {/* Almacén */}
            <SubMenu icon={<FolderIcon className="h-6 w-6" />} label="Almacén" open={almOpen} setOpen={setAlmOpen} roles={['logistica','dueño']}>
              <Item href="inventario_almacen.index" label="Inventario Almacén" icon={<ClipboardIcon className="h-5 w-5" />} roles={['logistica','dueño']} />
              <Item href="entradas_almacen.index"   label="Entradas Almacén"   icon={<ArrowDownCircleIcon className="h-5 w-5" />} roles={['logistica','dueño']} />
              <Item href="salidas_almacen.index"    label="Salidas Almacén"    icon={<ArrowUpCircleIcon className="h-5 w-5" />} roles={['logistica','dueño']} />
            </SubMenu>

            {/* Inventario Local y derivados */}
            <Item href="inventario_local.index"        label="Inventario Local"     icon={<HomeIcon className="h-6 w-6" />}       roles={['cajera','cremas']} />
            <Item href="entradas_local.index"          label="Entradas Local"       icon={<ArrowDownCircleIcon className="h-6 w-6" />} roles={['cajera','cremas']} />
            <Item href="salidas_local.index"           label="Salidas Local"        icon={<ArrowUpCircleIcon className="h-6 w-6" />} roles={['cajera','cremas']} />
            <Item href="historial_inventario_local.index" label="Historial Local"  icon={<ClockIcon className="h-6 w-6" />} roles={['admin','dueño']} />
            <Item href="discrepancia_inventario_local.index" label="Discrepancias" icon={<ShieldExclamationIcon className="h-6 w-6" />} roles={['admin','dueño']} />
            <Item href="requerimientos_local.index"    label="Requerimientos Local" icon={<ClipboardIcon className="h-6 w-6" />} roles={['logistica','cajera']} />

            {/* Man. Gastos */}
            <SubMenu icon={<FolderOpenIcon className="h-6 w-6" />} label="Man. Gastos" open={gastoOpen} setOpen={setGastoOpen} roles={['soporte']}>
              <Item href="tipos_gastos.index"           label="Tipos de Gastos"        icon={<ListBulletIcon className="h-5 w-5" />} roles={['soporte']} />
              <Item href="clasificaciones_gastos.index" label="Clasificaciones"        icon={<FunnelIcon className="h-5 w-5" />} roles={['soporte']} />
            </SubMenu>

            {/* Historial almacén & Req. Inventario */}
            <Item href="historial_inventario.index"  label="Historial Almacén"     icon={<ArchiveBoxIcon className="h-6 w-6" />} roles={['admin']} />
            <Item href="requerimiento_almacen.index" label="Req. Inventario"       icon={<ClipboardDocumentListIcon className="h-6 w-6" />} roles={['admin','logistica']} />
          </ul>
        </nav>

        <div className="p-4 border-t border-white/20 text-center text-sm text-white">
          © {new Date().getFullYear()} GESTVENIN
        </div>
      </aside>

      {/* =========== SIDEBAR MOBILE =========== */}
      {mobileOpen && <div className="fixed inset-0 z-20 bg-black/50 md:hidden" onClick={() => setMobileOpen(false)} />}
      <aside className={`fixed inset-y-0 left-0 z-30 w-64 bg-gradient-to-br from-[#0055D4] to-[#0070F3] p-4 md:hidden transform ${mobileOpen ? '' : '-translate-x-full'} transition`}>
        <button className="mb-4 text-white font-bold" onClick={() => setMobileOpen(false)}>×</button>
        {/* Por brevedad puedes renderizar solo algunos links o reusar el mismo conjunto */}
        <nav>
          <ul className="space-y-1">
            <Item href="dashboard" label="Inicio" icon={<HomeIcon className="h-6 w-6" />} roles={[]} />
            {is('soporte') && <Item href="users.index" label="Gestionar Usuarios" icon={<UserPlusIcon className="h-6 w-6" />} roles={['soporte']} />}
            {/* … agrega los que necesites */}
          </ul>
        </nav>
      </aside>

      {/* =========== CONTENIDO =========== */}
      <div className="flex flex-1 flex-col overflow-hidden">
        <header className="flex items-center justify-between h-16 bg-white border-b px-4">
          <div className="flex items-center gap-2">
            <button onClick={() => setMobileOpen(true)} className="md:hidden text-gray-600"><Bars3Icon className="h-6 w-6" /></button>
            <span className="font-bold text-[#0055D4]">GESTVENIN</span>
          </div>
          <div className="flex items-center gap-4">
            <button className="text-gray-600"><BellIcon className="h-6 w-6" /></button>
            <div className="relative">
              <button onClick={() => setUserDD(!userDD)} className="flex items-center gap-2">
                <UserCircleIcon className="h-8 w-8 text-gray-500" />
                <span className="hidden sm:block text-sm font-medium text-gray-700">{auth.user.name}</span>
              </button>
              {userDD && (
                <div className="absolute right-0 mt-2 w-48 bg-white border rounded shadow">
                  <button onClick={() => router.visit(route('profile.edit'))} className="flex items-center gap-2 w-full px-4 py-2 hover:bg-gray-100">
                    <UserCircleIcon className="h-5 w-5" /><span>Ver perfil</span>
                  </button>
                  <button onClick={() => router.post(route('logout'))} className="flex items-center gap-2 w-full px-4 py-2 text-red-600 hover:bg-gray-100">
                    <ArrowRightOnRectangleIcon className="h-5 w-5" /><span>Cerrar sesión</span>
                  </button>
                </div>
              )}
            </div>
          </div>
        </header>
        <main className="flex-1 overflow-auto p-4">{children}</main>
      </div>
    </div>
  );
}
