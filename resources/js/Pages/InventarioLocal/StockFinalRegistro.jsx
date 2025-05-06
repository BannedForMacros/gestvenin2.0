// resources/js/Pages/InventarioLocal/StockFinalRegistro.jsx
import React, { useState, useEffect } from 'react'
import { Head, router } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import {
  Button,
  Input,
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Chip,
  Card,
  CardBody,
  CardFooter,
  Progress,
} from '@heroui/react'
import { format } from 'date-fns'
import { Eye, Search } from 'lucide-react'
import { debounce } from 'lodash'
import EntradasModal from './EntradasModal'

export default function StockFinalRegistro({ auth, local, inventarios, fecha, stockGuardado }) {
  const [selectedDate, setSelectedDate] = useState(fecha || format(new Date(), 'yyyy-MM-dd'))
  const [stockData, setStockData]         = useState([])
  const [filteredData, setFilteredData]   = useState([])
  const [searchTerm, setSearchTerm]       = useState('')
  const [showModal, setShowModal]         = useState(false)
  const [confirmOpen, setConfirmOpen]     = useState(false)
  const [loading, setLoading]             = useState(false)

  // Formatear inventarios
  useEffect(() => {
    if (!inventarios) return
    const fmt = inventarios.map(i => ({
      ...i,
      stock_inicial: i.stock_inicial || 0,
      entradas:      i.entradas || 0,
      stock_final:   i.stock_final ?? i.stock_inicial ?? 0,
    }))
    setStockData(fmt)
    setFilteredData(fmt)
  }, [inventarios])

  // Filtrar nombre/categoría
  const handleSearch = debounce(q => {
    setSearchTerm(q)
    if (!q.trim()) return setFilteredData(stockData)
    const low = q.toLowerCase()
    setFilteredData(stockData.filter(i =>
      i.producto.toLowerCase().includes(low) ||
      i.categoria.toLowerCase().includes(low)
    ))
  }, 300)

  // Al cambiar fecha, navegamos por Inertia
  const handleDateChange = e => {
    const d = e.target.value
    setSelectedDate(d)
    router.get(
      route('inventario_local.registrar_stock_final', {
        local_id: local.id,
        fecha: d
      }),
      {}, // no query extra
      { preserveState: false }
    )
  }

  // Cambiar stock final en tabla
  const handleStockChange = (id, val) => {
    const next = stockData.map(i =>
      i.producto_almacen_id === id
        ? {...i, stock_final: val}
        : i
    )
    setStockData(next)
    setFilteredData(next)
  }

  // Guardar via Inertia
  const confirmGuardar = () => {
    setLoading(true)
    const fd = new FormData()
    fd.append('fecha', selectedDate)
    stockData.forEach(i => {
      fd.append(`stocks[${i.producto_almacen_id}]`, i.stock_final)
      fd.append(`stocks_iniciales[${i.producto_almacen_id}]`, i.stock_inicial)
    })
    router.post(
      route('inventario_local.guardar_stock_final', local.id),
      fd,
      {
        onSuccess: () => {
          setLoading(false)
          setConfirmOpen(false)
        },
        onError: () => setLoading(false),
      }
    )
  }

  return (
    <AuthenticatedLayout user={auth.user} header={<h2>Registrar Stock Final</h2>}>
      <Head title="Registrar Stock Final" />

      <Card className="mb-6 p-4">
        <div className="flex gap-4 flex-wrap items-center">
          <Input
            type="date"
            value={selectedDate}
            onChange={handleDateChange}
            className="w-auto"
          />
          <Button onPress={() => handleDateChange({ target: { value: selectedDate } })}>
            Cargar Datos
          </Button>
          <Button
            color="success"
            startContent={<Eye size={16} />}
            onPress={() => setShowModal(true)}
          >
            Ver Entradas
          </Button>
          <div className="ml-auto flex items-center gap-2">
            <Input
              placeholder="Buscar..."
              startContent={<Search size={16} />}
              onChange={e => handleSearch(e.target.value)}
              isClearable
            />
          </div>
        </div>
      </Card>

      <Table aria-label="Stock Final">
        <TableHeader columns={[
          { name: 'Producto',       uid: 'producto' },
          { name: 'Categoría',      uid: 'categoria' },
          { name: 'Inicial (+Entr.)', uid: 'stock_inicial' },
          { name: 'Entradas',       uid: 'entradas' },
          { name: 'Final',          uid: 'stock_final' },
        ]}>
          {col => <TableColumn key={col.uid}>{col.name}</TableColumn>}
        </TableHeader>
        <TableBody
          items={filteredData}
          emptyContent={<div className="py-6 text-center">No hay productos</div>}
        >
          {row => (
            <TableRow key={row.producto_almacen_id}>
              <TableCell>{row.producto}</TableCell>
              <TableCell>{row.categoria}</TableCell>
              <TableCell>
                <div className="flex items-center gap-2">
                  <span>{row.stock_inicial}</span>
                  <Chip size="sm" color="warning" variant="flat">
                    +{row.entradas}
                  </Chip>
                </div>
              </TableCell>
              <TableCell>{row.entradas}</TableCell>
              <TableCell>
                <Input
                  type="number"
                  value={row.stock_final}
                  onChange={e =>
                    handleStockChange(row.producto_almacen_id, Number(e.target.value))
                  }
                  className="w-20"
                />
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>

      <div className="flex justify-end mt-4">
        <Button
          color="primary"
          onPress={() => setConfirmOpen(true)}
          isDisabled={stockGuardado || loading}
          isLoading={loading}
        >
          Guardar Stock Final
        </Button>
      </div>

      {/* ——— Modal Entradas ——— */}
      <EntradasModal
        isOpen={showModal}
        onClose={() => setShowModal(false)}
        localId={local.id}
        fecha={selectedDate}
      />

      {/* ——— Confirmación ——— */}
      {confirmOpen && (
        <Card className="fixed inset-0 m-auto w-96 bg-white shadow-lg z-50">
          <CardBody>
            <h3 className="text-lg font-semibold mb-4">Confirmar Cierre</h3>
            <p>¿Guardar stock final para {selectedDate}?</p>
          </CardBody>
          <CardFooter className="flex justify-end gap-2">
            <Button variant="flat" onPress={() => setConfirmOpen(false)}>
              Cancelar
            </Button>
            <Button color="danger" onPress={confirmGuardar} isLoading={loading}>
              Sí, Guardar
            </Button>
          </CardFooter>
        </Card>
      )}
    </AuthenticatedLayout>
  )
}
