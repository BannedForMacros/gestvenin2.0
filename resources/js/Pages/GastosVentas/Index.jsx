// resources/js/Pages/GastosVentas/Index.jsx
import React, { useState, useMemo } from 'react'
import { Head, usePage, router } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import {
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Input,
  Select,
  SelectItem,
  Button,
  Chip,
  Tooltip,
  Pagination,
} from '@heroui/react'
import {
  PencilSquareIcon,
  NoSymbolIcon,
  CheckIcon,
  MagnifyingGlassIcon,
} from '@heroicons/react/24/outline'
import Swal from 'sweetalert2'
import EditModal from './Components/EditModal'

export default function Index() {
  const {
    gastosVentas,
    locales,
    tiposGastos,
    clasificacionesGastos,
    auth,
    fechaActual,
  } = usePage().props

  const isAdmin = auth.roles.includes('admin') || auth.roles.includes('dueño')

  // Estados de búsqueda, filtro y paginación
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [currentPage, setCurrentPage] = useState(1)
  const rowsPerPage = 10

  // Estado para el modal de edición
  const [isEditOpen, setEditOpen] = useState(false)
  const [selected, setSelected] = useState(null)

  // Columnas de la tabla
  const columns = [
    { name: 'Descripción',   uid: 'descripcion' },
    { name: 'Monto',         uid: 'monto'       },
    { name: 'Fecha',         uid: 'fecha_gasto' },
    ...(isAdmin ? [{ name: 'Local', uid: 'local' }] : []),
    { name: 'Tipo',          uid: 'tipo'        },
    { name: 'Clasificación', uid: 'clasificacion' },
    { name: 'Comprobante',   uid: 'comprobante' },
    { name: 'Estado',        uid: 'activo'      },
    { name: 'Acciones',      uid: 'actions'     },
  ]

  // Mapea props a filas planas
  const rows = gastosVentas.map(g => ({
    id:            g.id,
    descripcion:   g.descripcion || '-',
    monto:         Number(g.monto).toFixed(2),
    fecha_gasto:   g.fecha_gasto,
    local:         g.local?.nombre_local || '-',
    tipo:          g.tipoGasto?.nombre || '-',
    clasificacion: g.clasificacionGasto?.nombre || '-',
    comprobante:   g.comprobante_de_pago || '-',
    activo:        g.activo,
  }))

  // Filtrado client-side
  const filtered = useMemo(() => {
    let data = rows
    if (search) {
      const q = search.toLowerCase()
      data = data.filter(r =>
        Object.values(r).some(v =>
          String(v).toLowerCase().includes(q)
        )
      )
    }
    if (statusFilter === 'active')      data = data.filter(r => r.activo)
    else if (statusFilter === 'inactive') data = data.filter(r => !r.activo)
    return data
  }, [rows, search, statusFilter])

  // Paginación
  const totalPages = Math.ceil(filtered.length / rowsPerPage)
  const paginated = useMemo(() => {
    const start = (currentPage - 1) * rowsPerPage
    return filtered.slice(start, start + rowsPerPage)
  }, [filtered, currentPage])

  // Renderizador de celdas especiales
  const renderCell = (row, key) => {
    switch (key) {
      case 'monto':
        return <>S/ {row.monto}</>
      case 'fecha_gasto':
        return new Date(row.fecha_gasto).toLocaleDateString('es-PE')
      case 'activo':
        return (
          <Chip
            size="sm"
            variant={row.activo ? 'solid' : 'bordered'}
            color={row.activo ? 'success' : 'danger'}
            className="capitalize"
          >
            {row.activo ? 'Activo' : 'Inactivo'}
          </Chip>
        )
      case 'actions':
        return (
          <div className="flex items-center gap-2 justify-center">
            {/* BOTÓN EDITAR */}
            <Tooltip content="Editar gasto">
              <Button
                isIconOnly
                size="sm"
                variant="light"
                color="warning"
                onPress={() => {
                  const g = gastosVentas.find(x => x.id === row.id)
                  setSelected(g)
                  setEditOpen(true)
                }}
              >
                <PencilSquareIcon className="h-5 w-5" />
              </Button>
            </Tooltip>

            {/* BOTÓN INACTIVAR / ACTIVAR */}
            <Tooltip content={row.activo ? 'Inactivar gasto' : 'Activar gasto'}>
              <Button
                isIconOnly
                size="sm"
                variant="light"
                color={row.activo ? 'secondary' : 'success'}
                onPress={() => {
                  Swal.fire({
                    title: row.activo
                      ? '¿Deseas inactivar este gasto?'
                      : '¿Deseas activar este gasto?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'No',
                  }).then(result => {
                    if (result.isConfirmed) {
                      // Aquí llamamos a tu toggleStatus en el backend
                      router.get(
                        route('gastos_ventas.toggleStatus', row.id),
                        {},
                        { preserveState: true }
                      )
                    }
                  })
                }}
              >
                {row.activo
                  ? <NoSymbolIcon className="h-5 w-5" />
                  : <CheckIcon    className="h-5 w-5" />}
              </Button>
            </Tooltip>
          </div>
        )
      default:
        return row[key]
    }
  }

  return (
    <AuthenticatedLayout>
      <Head title="Gastos de Venta" />

      {/* Cabecera + botón crear */}
      <div className="flex justify-between items-center mb-4">
        <h1 className="text-2xl font-semibold">Lista de Gastos de Venta</h1>
        <Button
          color="primary"
          startContent={<MagnifyingGlassIcon className="h-5 w-5" />}
          onPress={() => router.get(route('gastos_ventas.create'))}
        >
          Crear Gasto
        </Button>
      </div>

      {/* Filtros */}
      <div className="flex flex-col md:flex-row gap-4 mb-4 items-end">
        <Input
          placeholder="Buscar..."
          startContent={<MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />}
          value={search}
          onChange={e => { setSearch(e.target.value); setCurrentPage(1) }}
          className="w-full md:w-1/3"
          size="sm"
          variant="bordered"
        />
        <Select
          placeholder="Estado"
          value={statusFilter}
          onValueChange={v => { setStatusFilter(v); setCurrentPage(1) }}
          size="sm"
        >
          <SelectItem value="all">Todos</SelectItem>
          <SelectItem value="active">Activos</SelectItem>
          <SelectItem value="inactive">Inactivos</SelectItem>
        </Select>
      </div>

      {/* Tabla */}
      <Table
        aria-label="Gastos de Venta"
        bottomContent={
          totalPages > 1 && (
            <div className="flex justify-center py-4">
              <Pagination
                page={currentPage}
                total={totalPages}
                onChange={setCurrentPage}
                showControls
              />
            </div>
          )
        }
      >
        <TableHeader columns={columns}>
          {col => (
            <TableColumn key={col.uid} className="text-sm font-medium">
              {col.name}
            </TableColumn>
          )}
        </TableHeader>
        <TableBody
          items={paginated}
          emptyContent={
            <div className="py-8 text-center text-gray-500">
              No hay gastos de Venta para la fecha de hoy.
            </div>
          }
        >
          {item => (
            <TableRow key={item.id} className="hover:bg-gray-50">
              {colKey => (
                <TableCell>{renderCell(item, colKey)}</TableCell>
              )}
            </TableRow>
          )}
        </TableBody>
      </Table>

      {/* Modal de edición */}
      <EditModal
        isOpen={isEditOpen}
        onClose={() => setEditOpen(false)}
        gasto={selected}
        locales={locales}
        tiposGastos={tiposGastos}
        clasificacionesGastos={clasificacionesGastos}
        auth={auth}
      />
    </AuthenticatedLayout>
  )
}
