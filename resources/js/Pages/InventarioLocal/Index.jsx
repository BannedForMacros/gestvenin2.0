// resources/js/Pages/InventarioLocal/Index.jsx
import React, { useState, useMemo, useEffect } from 'react'
import { Head, usePage } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { router } from '@inertiajs/react'
import {
  Button,
  Select,
  SelectItem,
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Input,
  Card,
  CardBody,
  CardHeader,
  CardFooter,
  Chip,
  Pagination,
  Progress,
  Tabs,
  Tab,
  Divider,
} from '@heroui/react'
import Swal from 'sweetalert2'
import {
  Search,
  SortAsc,
  SortDesc,
  AlertTriangle,
  Package2,
  Table as TableIcon,
  RefreshCcw,
  FileText,
  Filter,
} from 'lucide-react'

export default function Index() {
  const { inventarios, locales, localSeleccionado, stockGuardado, categorias, auth } =
    usePage().props

  const roles = auth.roles || []
  const isAdmin = roles.includes('dueño') || roles.includes('admin')
  const isCajera = roles.includes('cajera') || roles.includes('cremas')

  /* ---------- ESTADO ---------- */
  const [selectedLocal, setSelectedLocal] = useState(
    localSeleccionado ? String(localSeleccionado.id) : ''
  )
  const [selectedCat, setSelectedCat] = useState('')          // ← categoría
  const [sortAsc, setSortAsc] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')
  const [page, setPage] = useState(1)
  const [activeView, setActiveView] = useState('table')
  const [highlightLowStock, setHighlightLowStock] = useState(true)
  const [showFavorites, setShowFavorites] = useState(false)
  const [favorites, setFavorites] = useState([])
  const rowsPerPage = 10

  const [isGeneratingReq, setIsGeneratingReq] = useState(false)
  const [isCerrandoStock, setIsCerrandoStock] = useState(false)

  /* ---------- FAVORITOS ---------- */
  useEffect(() => {
    const saved = localStorage.getItem('inventarioFavorites')
    if (saved) {
      try {
        setFavorites(JSON.parse(saved))
      } catch {}
    }
  }, [])

  const toggleFavorite = (id) => {
    const next = favorites.includes(id)
      ? favorites.filter((x) => x !== id)
      : [...favorites, id]
    setFavorites(next)
    localStorage.setItem('inventarioFavorites', JSON.stringify(next))
  }

  /* ---------- CAMBIO DE LOCAL ---------- */
  const handleLocalChange = (val) => {
    setSelectedLocal(val)
    setPage(1)
    router.get(
      route('inventario_local.index'),
      { local_id: val },
      { preserveState: true }
    )
  }

  /* ---------- CERRAR STOCK ---------- */
  const handleCerrarStock = () => {
    setIsCerrandoStock(true)
    Swal.fire({
      title: '¿Cerrar stock?',
      text: 'Esta acción registrará el stock final del día',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, cerrar stock',
      cancelButtonText: 'Cancelar',
    }).then((result) => {
      if (result.isConfirmed) {
        router.get(
          route('inventario_local.registrar_stock_final', {
            local_id: localSeleccionado.id,
          }),
          {},
          {
            onSuccess: () => {
              Swal.fire('¡Éxito!', 'Stock cerrado correctamente', 'success')
              setIsCerrandoStock(false)
            },
            onError: () => {
              Swal.fire('Error', 'No se pudo cerrar el stock', 'error')
              setIsCerrandoStock(false)
            },
          }
        )
      } else {
        setIsCerrandoStock(false)
      }
    })
  }

  /* ---------- GENERAR REQUERIMIENTOS ---------- */
  const handleGenerar = () => {
    setIsGeneratingReq(true);
    Swal.fire({ /* loading… */ });
  
    router.post(route('requerimientos_local.generar'), { local_id: localSeleccionado.id }, {
      onSuccess: () => {
        setIsGeneratingReq(false);
        Swal.fire('¡Listo!', 'Requerimientos generados', 'success')
          .then(() => router.get(route('requerimientos_local.index')));
      },
      onError: () => {
        setIsGeneratingReq(false);
        Swal.fire('Error', 'Falló la generación', 'error');
      },
    });
  }

  /* ---------- HELPERS DE STOCK ---------- */
  const getStockLevel = (n) => {
    const qty = parseFloat(n)
    if (isNaN(qty)) return 'unknown'
    if (qty <= 3) return 'low'
    if (qty <= 10) return 'medium'
    return 'good'
  }
  const getStockColor = (level) =>
    ({ low: 'danger', medium: 'warning', good: 'success', unknown: 'default' }[level] || 'default')

  const renderStockIndicator = (row) => {
    const val = row.status === 'low' ? 20 : row.status === 'medium' ? 50 : 100
    return (
      <div className="flex items-center gap-2">
        <Progress size="sm" value={val} color={getStockColor(row.status)} className="max-w-md" />
        <span className="text-sm">{row.cantidad}</span>
      </div>
    )
  }

  /* ---------- COLUMNAS ---------- */
  const columns = useMemo(() => {
    const cols = [
      { name: 'Producto', uid: 'producto' },
      { name: 'Categoría', uid: 'categoria' },
      { name: 'Stock', uid: 'cantidad' },
      { name: 'Estado', uid: 'status' },
    ]
    if (isAdmin) {
      cols.push(
        { name: 'Precio Unitario', uid: 'pu' },
        { name: 'Precio Total', uid: 'pt' }
      )
    }
    cols.push({ name: 'Acciones', uid: 'actions' })
    return cols
  }, [isAdmin])

  /* ---------- PROCESAMIENTO / FILTROS ---------- */
  const processedRows = useMemo(() => {
    let data = inventarios

    /* ------ FILTRO POR CATEGORÍA ------ */
    if (selectedCat) {
      data = data.filter((inv) => {
        const catId = inv.producto_almacen?.categoria?.id
        // normalizamos ambos a string para evitar problemas de tipo
        return String(catId) === String(selectedCat)
      })
    }

    /* ------ BÚSQUEDA ------ */
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase()
      data = data.filter((inv) =>
        inv.producto_almacen?.nombre?.toLowerCase().includes(q)
      )
    }

    /* ------ FAVORITOS ------ */
    if (showFavorites) {
      data = data.filter((inv) => favorites.includes(inv.id))
    }

    /* ------ MAPEO Y ORDEN ------ */
    return data
      .map((inv) => {
        const p = inv.producto_almacen || {}
        const raw = Number(inv.cantidad ?? 0)
        const qty = raw.toFixed(2).replace(/\.00$/, '')
        const lvl = getStockLevel(raw)
        return {
          key: inv.id,
          producto: p.nombre || '–',
          categoria: p.categoria?.nombre || '–',
          cantidad: `${qty} ${p.unidad_medida?.nombre || ''}`.trim(),
          status: lvl,
          pu:
            inv.precio_unitario != null
              ? `S/ ${Number(inv.precio_unitario).toFixed(2)}`
              : '–',
          pt:
            inv.precio_total != null
              ? `S/ ${Number(inv.precio_total).toFixed(2)}`
              : '–',
          isFavorite: favorites.includes(inv.id),
          rawQty: raw,
        }
      })
      .sort((a, b) =>
        sortAsc
          ? a.producto.localeCompare(b.producto)
          : b.producto.localeCompare(a.producto)
      )
  }, [
    inventarios,
    selectedCat,
    searchQuery,
    showFavorites,
    favorites,
    sortAsc,
  ])

  /* ---------- PAGINACIÓN Y DERIVADOS ---------- */
  const lowStockItems = useMemo(
    () => processedRows.filter((r) => r.status === 'low'),
    [processedRows]
  )
  const pages = Math.ceil(processedRows.length / rowsPerPage)
  const paginatedRows = useMemo(
    () =>
      processedRows.slice((page - 1) * rowsPerPage, page * rowsPerPage),
    [processedRows, page]
  )
  const topStockItems = useMemo(
    () => [...processedRows].sort((a, b) => b.rawQty - a.rawQty).slice(0, 5),
    [processedRows]
  )

  /* ====================================================================== */
  /* ============================= RENDER ================================= */
  /* ====================================================================== */

  return (
    <AuthenticatedLayout>
      <Head title="Inventario del Local" />

      <div className="max-w-7xl mx-auto px-2 sm:px-4 md:px-6">
        {/* ---------------- CABECERA Y FILTROS INICIALES ---------------- */}
        <div className="bg-white shadow-sm rounded-lg p-4 mb-6">
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <h1 className="text-2xl font-semibold">
              Inventario del Local:{' '}
              <span className="text-primary-600">
                {localSeleccionado?.nombre_local || '— Seleccione un Local —'}
              </span>
            </h1>
            <div className="flex flex-wrap gap-2">
              {localSeleccionado && (
                <>
                  <Button
                    color="success"
                    variant="solid"
                    startContent={<RefreshCcw size={18} />}
                    onPress={handleCerrarStock}
                    isDisabled={(isCajera && stockGuardado) || isCerrandoStock}
                    isLoading={isCerrandoStock}
                  >
                    {isCajera && stockGuardado
                      ? 'Stock Cerrado'
                      : 'Cerrar Stock'}
                  </Button>
                  <Button
                    color="warning"
                    variant="solid"
                    startContent={<FileText size={18} />}
                    onPress={handleGenerar}
                    isDisabled={isGeneratingReq}
                    isLoading={isGeneratingReq}
                  >
                    Generar Requerimientos
                  </Button>
                </>
              )}
            </div>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {isAdmin && locales.length > 0 && (
              <div className="flex items-center gap-2">
                <span className="font-medium whitespace-nowrap">Local:</span>
                <Select
                  value={selectedLocal}
                  onValueChange={handleLocalChange}
                  className="w-full"
                  startContent={
                    <Package2 size={18} className="text-gray-500" />
                  }
                  placeholder="Seleccione local..."
                >
                  {locales.map((l) => (
                    <SelectItem key={String(l.id)}>
                      {l.nombre_local}
                    </SelectItem>
                  ))}
                </Select>
              </div>
            )}
          </div>
        </div>

        {/* ---------------- ESTADÍSTICAS ---------------- */}
        {localSeleccionado && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <Card className="bg-blue-50">
              <CardBody className="flex justify-between items-center">
                <div>
                  <p className="text-sm text-gray-500">Total Productos</p>
                  <p className="text-2xl font-bold">{processedRows.length}</p>
                </div>
                <Package2 size={32} className="text-blue-500" />
              </CardBody>
            </Card>
            <Card className="bg-red-50">
              <CardBody className="flex justify-between items-center">
                <div>
                  <p className="text-sm text-gray-500">Stock Bajo</p>
                  <p className="text-2xl font-bold">{lowStockItems.length}</p>
                </div>
                <AlertTriangle size={32} className="text-red-500" />
              </CardBody>
            </Card>
            <Card className="bg-green-50">
              <CardBody className="flex justify-between items-center">
                <div>
                  <p className="text-sm text-gray-500">Categorías</p>
                  <p className="text-2xl font-bold">{categorias.length}</p>
                </div>
                <Filter size={32} className="text-green-500" />
              </CardBody>
            </Card>
          </div>
        )}

        {/* ---------------- ACCIONES Y TABS ---------------- */}
        <div className="flex flex-wrap justify-between items-center mb-4 gap-2">
          <div className="flex gap-2">
            <Button
              variant="flat"
              onPress={() => setSortAsc((p) => !p)}
              startContent={sortAsc ? <SortAsc size={18} /> : <SortDesc size={18} />}
              auto
            >
              {sortAsc ? 'A → Z' : 'Z → A'}
            </Button>
            <Button
              variant="flat"
              color={showFavorites ? 'primary' : 'default'}
              onPress={() => setShowFavorites((p) => !p)}
              auto
            >
              {showFavorites ? 'Todos los productos' : 'Mostrar favoritos'}
            </Button>
            <Button
              variant="flat"
              color={highlightLowStock ? 'danger' : 'default'}
              onPress={() => setHighlightLowStock((p) => !p)}
              auto
            >
              {highlightLowStock ? 'Ocultar alarmas' : 'Mostrar alarmas de stock'}
            </Button>
          </div>
          <Tabs selectedKey={activeView} onSelectionChange={setActiveView} className="mb-6">
            <Tab
              key="table"
              title={
                <div className="flex items-center gap-2">
                  <TableIcon size={18} />
                  <span>Tabla</span>
                </div>
              }
            />
            <Tab
              key="cards"
              title={
                <div className="flex items-center gap-2">
                  <Package2 size={18} />
                  <span>Tarjetas</span>
                </div>
              }
            />
          </Tabs>
        </div>

        {/* ================================================================ */}
        {/* =========================== TABLA ============================== */}
        {/* ================================================================ */}
        {activeView === 'table' && (
          <Card className="mb-6">
            {/* ---------- BARRA DE FILTROS ---------- */}
            <div className="p-4 border-b">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* ------ CATEGORÍAS ------ */}
                <div className="flex items-center gap-2">
                  <span className="font-medium whitespace-nowrap">Categoría:</span>

                  <Select
                    selectedKeys={selectedCat ? new Set([selectedCat]) : new Set()}
                    /* ← EL CALLBACK CORRECTO */
                    onSelectionChange={(keys) => {
                      /* extraemos la primera clave del Set */
                      const key = Array.from(keys)[0] ?? ''
                      setSelectedCat(String(key))
                      setPage(1)
                    }}
                    className="w-full"
                    startContent={<Filter size={18} className="text-gray-500" />}
                    placeholder="Todas las categorías"
                  >
                    <SelectItem key="">Todas</SelectItem>
                    {categorias.map((c) => (
                      <SelectItem key={String(c.id)}>{c.nombre}</SelectItem>
                    ))}
                  </Select>
                </div>

                {/* ------ BÚSQUEDA ------ */}
                <div className="flex items-center gap-2">
                  <Input
                    placeholder="Buscar producto..."
                    value={searchQuery}
                    onChange={(e) => {
                      setSearchQuery(e.target.value)
                      setPage(1)
                    }}
                    startContent={<Search size={18} className="text-gray-500" />}
                    className="w-full"
                    isClearable
                    onClear={() => setSearchQuery('')}
                  />
                </div>
              </div>

              {/* Resumen del filtrado */}
              <div className="mt-3 text-sm text-gray-600">
                Mostrando {paginatedRows.length} de {processedRows.length} productos
                {selectedCat && ' (filtrado por categoría)'}
                {searchQuery && ` (búsqueda: "${searchQuery}")`}
              </div>
            </div>

            {/* ---------- TABLA PRINCIPAL ---------- */}
            <Table aria-label="Inventario Local">
  <TableHeader columns={columns}>
    {col => <TableColumn key={col.uid}>{col.name}</TableColumn>}
  </TableHeader>

  <TableBody
    emptyContent={
      <div className="py-8 text-center text-gray-500">
        {searchQuery.trim()
          ? `No se encontraron productos para "${searchQuery}".`
          : selectedCat
          ? 'No hay productos en esta categoría.'
          : showFavorites
          ? 'No tienes favoritos aún.'
          : 'No hay datos para mostrar.'}
      </div>
    }
  >
    {paginatedRows.map(row => (
      <TableRow
        key={row.key}
        className={
          highlightLowStock && row.status === 'low'
            ? 'bg-danger-50 dark:bg-danger-900/20'
            : ''
        }
      >
        {columns.map(col => {
          // “Acciones” column
          if (col.uid === 'actions') {
            return (
              <TableCell key="actions">
                <Button
                  isIconOnly
                  variant="light"
                  onPress={() => toggleFavorite(row.key)}
                  className={row.isFavorite ? 'text-warning' : ''}
                >
                  {row.isFavorite ? '★' : '☆'}
                </Button>
              </TableCell>
            )
          }

          // “Estado” column as a colored badge
          if (col.uid === 'status') {
            return (
              <TableCell key="status">
                <Chip size="sm" color={getStockColor(row.status)} variant="flat">
                  {row.status === 'low'
                    ? 'Bajo'
                    : row.status === 'medium'
                    ? 'Medio'
                    : 'Bueno'}
                </Chip>
              </TableCell>
            )
          }

          // “Stock” column with progress bar
          if (col.uid === 'cantidad') {
            return <TableCell key="cantidad">{renderStockIndicator(row)}</TableCell>
          }

          // All other simple text columns
          return <TableCell key={col.uid}>{row[col.uid]}</TableCell>
        })}
      </TableRow>
    ))}
  </TableBody>
</Table>


            {/* PAGINACIÓN */}
            {pages > 1 && (
              <div className="flex justify-center py-4">
                <Pagination total={pages} page={page} onChange={setPage} />
              </div>
            )}
          </Card>
        )}

        {/* ================================================================ */}
        {/* ========================== TARJETAS ============================ */}
        {/* ================================================================ */}
        {activeView === 'cards' && (
          <>
            {/* ---------- TOP STOCK ---------- */}
            <div className="mb-6">
              <h2 className="text-xl font-semibold mb-4">Productos con Mayor Stock</h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                {topStockItems.map((item) => (
                  <Card key={item.key} className="border shadow-sm">
                    <CardHeader className="flex justify-between items-center">
                      <h4 className="font-semibold truncate">{item.producto}</h4>
                      <Button
                        isIconOnly
                        size="sm"
                        variant="light"
                        onPress={() => toggleFavorite(item.key)}
                        className={item.isFavorite ? 'text-warning' : ''}
                      >
                        {item.isFavorite ? '★' : '☆'}
                      </Button>
                    </CardHeader>
                    <CardBody>
                      <p className="text-small text-gray-500">Categoría</p>
                      <p>{item.categoria}</p>
                      <p className="text-small text-gray-500 mt-2">Stock</p>
                      <div className="flex items-center gap-2">
                        <Progress
                          size="sm"
                          value={
                            item.status === 'low'
                              ? 20
                              : item.status === 'medium'
                              ? 50
                              : 100
                          }
                          color={getStockColor(item.status)}
                          className="max-w-md"
                        />
                        <span className="font-medium">{item.cantidad}</span>
                      </div>
                      {isAdmin && (
                        <p className="text-small text-gray-500 mt-2">
                          Precio: {item.pu} / {item.pt}
                        </p>
                      )}
                    </CardBody>
                    <CardFooter>
                      <Chip size="sm" color={getStockColor(item.status)} variant="flat">
                        {item.status === 'low'
                          ? 'Bajo'
                          : item.status === 'medium'
                          ? 'Medio'
                          : 'Bueno'}
                      </Chip>
                    </CardFooter>
                  </Card>
                ))}
              </div>
            </div>

            {/* ---------- LISTADO COMPLETO ---------- */}
            <h2 className="text-xl font-semibold mb-4">Inventario Completo</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
              {paginatedRows.map((item) => (
                <Card
                  key={item.key}
                  className={`border shadow-sm ${
                    item.status === 'low' && highlightLowStock
                      ? 'border-danger bg-danger-50 dark:bg-danger-900/20'
                      : ''
                  }`}
                >
                  <CardHeader className="flex justify-between items-center">
                    <h4 className="font-semibold truncate">{item.producto}</h4>
                    <Button
                      isIconOnly
                      size="sm"
                      variant="light"
                      onPress={() => toggleFavorite(item.key)}
                      className={item.isFavorite ? 'text-warning' : ''}
                    >
                      {item.isFavorite ? '★' : '☆'}
                    </Button>
                  </CardHeader>
                  <CardBody>
                    <p className="text-small text-gray-500">Categoría</p>
                    <p>{item.categoria}</p>
                    <p className="text-small text-gray-500 mt-2">Stock</p>
                    <div className="flex items-center gap-2">
                      <Progress
                        size="sm"
                        value={
                          item.status === 'low'
                            ? 20
                            : item.status === 'medium'
                            ? 50
                            : 100
                        }
                        color={getStockColor(item.status)}
                        className="max-w-md"
                      />
                      <span className="font-medium">{item.cantidad}</span>
                    </div>
                    {isAdmin && (
                      <p className="text-small text-gray-500 mt-2">
                        Precio: {item.pu} / {item.pt}
                      </p>
                    )}
                  </CardBody>
                  <CardFooter>
                    <Chip size="sm" color={getStockColor(item.status)} variant="flat">
                      {item.status === 'low'
                        ? 'Bajo'
                        : item.status === 'medium'
                        ? 'Medio'
                        : 'Bueno'}
                    </Chip>
                  </CardFooter>
                </Card>
              ))}
            </div>

            {pages > 1 && (
              <div className="flex justify-center mb-6">
                <Pagination total={pages} page={page} onChange={setPage} showControls />
              </div>
            )}
          </>
        )}

        <Divider className="my-6" />
        <div className="text-center text-gray-500 text-sm">
          © {new Date().getFullYear()} MacSoft. Todos los derechos reservados.
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
