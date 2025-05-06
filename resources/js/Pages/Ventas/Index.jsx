// resources/js/Pages/Ventas/Index.jsx
import React, { useState, useMemo } from 'react';
import { Head, usePage, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
  Card,
  CardBody,
  CardHeader,
  Input,
  Button,
  Tooltip,
  Badge,
  Chip,
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Divider,
  Dropdown,
  DropdownTrigger,
  DropdownMenu,
  DropdownItem,
  Pagination,
} from '@heroui/react';
import {
  PlusCircleIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  TrashIcon,
  PrinterIcon,
  AdjustmentsHorizontalIcon,
  ChevronDownIcon,
  CalendarIcon,
  CurrencyDollarIcon,
  BuildingStorefrontIcon,
  ClipboardDocumentListIcon,
} from '@heroicons/react/24/outline';
import Swal from 'sweetalert2';

export default function Index() {
  const { ventas, auth } = usePage().props;
  const isCajera = auth.roles.includes('cajera');

  // Cliente-side search y filtros
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [currentPage, setCurrentPage] = useState(1);
  const rowsPerPage = 10;
  
  const filteredVentas = useMemo(() => {
    let filtered = ventas;
    
    // Filtro de búsqueda
    if (search) {
      const lower = search.toLowerCase();
      filtered = filtered.filter(v =>
        JSON.stringify(v).toLowerCase().includes(lower)
      );
    }
    
    // Filtro de estado
    if (statusFilter !== 'all') {
      filtered = filtered.filter(v => 
        statusFilter === 'active' ? v.activo : !v.activo
      );
    }
    
    return filtered;
  }, [ventas, search, statusFilter]);
  
  // Paginación
  const pages = Math.ceil(filteredVentas.length / rowsPerPage);
  const paginatedVentas = useMemo(() => {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    return filteredVentas.slice(start, end);
  }, [filteredVentas, currentPage]);

  // Columnas
  const baseColumns = [
    { name: 'ID', uid: 'id' },
    { name: 'Productos', uid: 'productos' },
    { name: 'Total', uid: 'total' },
    { name: 'Local', uid: 'local' },
    { name: 'Estado', uid: 'estado' },
    { name: 'Pagos', uid: 'pagos' },
    { name: 'Acciones', uid: 'acciones' },
  ];
  const columns = useMemo(
    () => baseColumns.filter(c => !(isCajera && c.uid === 'local')),
    [isCajera]
  );

  // Render de celdas
  const renderCell = (v, key) => {
    switch (key) {
      case 'id':
        return (
          <div className="flex flex-col">
            <span className="font-medium text-primary">{v.id}</span>
            <span className="text-xs text-gray-500">
              {new Date(v.created_at).toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
              })}
            </span>
          </div>
        );

      case 'productos':
        return (
          <div className="flex flex-wrap gap-1 max-w-md">
            {v.detalles.map((d, i) => (
              <Badge 
                key={i} 
                color="primary" 
                variant="flat"
                className="py-1 px-2 text-xs"
              >
                {d.producto.nombre} ({d.cantidad})
              </Badge>
            ))}
          </div>
        );

      case 'total':
        return (
          <div className="flex items-center gap-2">
            <div className="p-1.5 rounded-full bg-green-100">
              <CurrencyDollarIcon className="h-4 w-4 text-green-600" />
            </div>
            <span className="text-green-600 font-semibold">
              S/ {Number(v.total).toFixed(2)}
            </span>
          </div>
        );

      case 'local':
        return (
          <div className="flex items-center gap-2">
            <div className="p-1.5 rounded-full bg-blue-100">
              <BuildingStorefrontIcon className="h-4 w-4 text-blue-600" />
            </div>
            <span className="font-medium">{v.local?.nombre_local || '—'}</span>
          </div>
        );

      case 'estado':
        return (
          <Chip
            className="capitalize"
            color={v.activo ? 'success' : 'danger'}
            size="sm"
            variant={v.activo ? 'solid' : 'bordered'}
            startContent={v.activo ? 
              <div className="h-2 w-2 rounded-full bg-white animate-pulse" /> : 
              null
            }
          >
            {v.activo ? 'Activo' : 'Inactivo'}
          </Chip>
        );

      case 'pagos':
        return (
          <div className="space-y-2">
            {v.pagos.map(p => (
              <div key={p.id} className="flex items-center gap-2">
                <Badge
                  color={
                    p.metodo_pago === 'Efectivo'
                      ? 'success'
                      : p.metodo_pago.startsWith('Yape')
                      ? 'secondary'
                      : p.metodo_pago === 'Plin'
                      ? 'primary'
                      : 'warning'
                  }
                  variant="flat"
                  className="py-1 px-2"
                >
                  {p.metodo_pago}
                </Badge>
                <span className="font-medium">S/ {Number(p.monto).toFixed(2)}</span>
              </div>
            ))}
          </div>
        );

      case 'acciones':
        return (
          <div className="flex gap-2 justify-center">
            {(!isCajera ||
              (Date.now() - Date.parse(v.created_at)) / 60000 < 3) && (
              <Tooltip content="Editar venta">
                <Link href={route('ventas.edit', v.id)}>
                  <Button 
                    isIconOnly 
                    variant="light" 
                    color="warning" 
                    size="sm"
                    className="text-yellow-600"
                  >
                    <PencilSquareIcon className="h-5 w-5" />
                  </Button>
                </Link>
              </Tooltip>
            )}
            <Tooltip content="Desactivar venta">
              <Button 
                isIconOnly 
                variant="light" 
                color="danger" 
                size="sm"
                onClick={() =>
                  Swal.fire({
                    title: '¿Desactivar esta venta?',
                    text: 'Esta acción no se puede revertir',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, desactivar',
                    cancelButtonText: 'Cancelar',
                  }).then(result => {
                    if (result.isConfirmed) {
                      router.delete(route('ventas.destroy', v.id));
                    }
                  })
                }
              >
                <TrashIcon className="h-5 w-5" />
              </Button>
            </Tooltip>
            <Tooltip content="Imprimir venta">
              <Button 
                as="a" 
                href={route('ventas.imprimir', v.id)} 
                target="_blank" 
                rel="noopener"
                isIconOnly 
                variant="light" 
                color="primary" 
                size="sm"
              >
                <PrinterIcon className="h-5 w-5" />
              </Button>
            </Tooltip>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <AuthenticatedLayout>
      <Head title="Listado de Ventas" />

      {/* Hero banner */}
      <div className="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-6 mb-6 shadow-lg">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-white mb-2">
              Gestión de Ventas
            </h1>
            <p className="text-blue-100">
              Administra todas tus ventas desde un solo lugar
            </p>
          </div>
          <div className="hidden md:block">
            <ClipboardDocumentListIcon className="h-16 w-16 text-white opacity-80" />
          </div>
        </div>
      </div>

      {/* Tarjetas de resumen */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <Card shadow="sm" className="border-none">
          <CardBody className="p-4">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-full bg-green-100">
                <CurrencyDollarIcon className="h-6 w-6 text-green-600" />
              </div>
              <div>
                <p className="text-sm font-medium text-gray-500">Total ventas</p>
                <p className="text-2xl font-bold text-green-600">
                  S/ {ventas.reduce((sum, v) => sum + Number(v.total), 0).toFixed(2)}
                </p>
              </div>
            </div>
          </CardBody>
        </Card>
        
        <Card shadow="sm" className="border-none">
          <CardBody className="p-4">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-full bg-blue-100">
                <ClipboardDocumentListIcon className="h-6 w-6 text-blue-600" />
              </div>
              <div>
                <p className="text-sm font-medium text-gray-500">Total registros</p>
                <p className="text-2xl font-bold text-blue-600">{ventas.length}</p>
              </div>
            </div>
          </CardBody>
        </Card>
        
        <Card shadow="sm" className="border-none">
          <CardBody className="p-4">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-full bg-purple-100">
                <CalendarIcon className="h-6 w-6 text-purple-600" />
              </div>
              <div>
                <p className="text-sm font-medium text-gray-500">Última venta</p>
                <p className="text-2xl font-bold text-purple-600">
                  {ventas.length > 0 ? new Date(ventas[0].created_at).toLocaleDateString() : '—'}
                </p>
              </div>
            </div>
          </CardBody>
        </Card>
      </div>

      <Card shadow="md" radius="lg" className="overflow-hidden">
        <CardHeader className="flex flex-col gap-4 md:flex-row justify-between items-start md:items-center p-4 bg-gray-50">
          {/* Título y botón crear */}
          <div className="flex items-center gap-4">
            <h2 className="text-xl font-semibold">Listado de Ventas</h2>
            <Link href={route('ventas.create')}>
              <Button
                startContent={<PlusCircleIcon className="h-5 w-5" />}
                color="primary"
                className="bg-gradient-to-r from-blue-500 to-blue-700"
              >
                Crear venta
              </Button>
            </Link>
          </div>

          {/* Búsqueda y filtros */}
          <div className="flex flex-col md:flex-row gap-2 w-full md:w-auto">
            <Input
              placeholder="Buscar venta..."
              startContent={<MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />}
              value={search}
              onChange={e => setSearch(e.target.value)}
              className="w-full md:w-64"
              size="sm"
              variant="bordered"
            />
            
            <Dropdown>
              <DropdownTrigger>
                <Button
                  variant="flat"
                  startContent={<AdjustmentsHorizontalIcon className="h-4 w-4" />}
                  endContent={<ChevronDownIcon className="h-4 w-4" />}
                  size="sm"
                >
                  Filtrar
                </Button>
              </DropdownTrigger>
              <DropdownMenu
                aria-label="Filtros"
                onAction={(key) => setStatusFilter(key)}
                selectedKeys={[statusFilter]}
                selectionMode="single"
              >
                <DropdownItem key="all">Todos</DropdownItem>
                <DropdownItem key="active">Activos</DropdownItem>
                <DropdownItem key="inactive">Inactivos</DropdownItem>
              </DropdownMenu>
            </Dropdown>
          </div>
        </CardHeader>
        
        <Divider />
        
        <CardBody className="p-0">
          <Table 
            aria-label="Ventas"
            bottomContent={
              pages > 1 ? (
                <div className="flex justify-center p-4">
                  <Pagination
                    color="primary"
                    page={currentPage}
                    total={pages}
                    onChange={setCurrentPage}
                    showControls
                  />
                </div>
              ) : null
            }
            classNames={{
              th: "bg-gray-50 text-gray-600",
              td: "py-3"
            }}
          >
            <TableHeader columns={columns}>
              {col => (
                <TableColumn key={col.uid} className="text-sm uppercase">
                  {col.name}
                </TableColumn>
              )}
            </TableHeader>
            <TableBody 
              items={paginatedVentas}
              emptyContent={
                <div className="py-8 text-center">
                  <p className="text-gray-500">No se encontraron ventas</p>
                </div>
              }
            >
              {venta => (
                <TableRow key={venta.id} className="hover:bg-gray-50 transition-colors">
                  {columnKey => (
                    <TableCell>{renderCell(venta, columnKey)}</TableCell>
                  )}
                </TableRow>
              )}
            </TableBody>
          </Table>
        </CardBody>
      </Card>
    </AuthenticatedLayout>
  );
}