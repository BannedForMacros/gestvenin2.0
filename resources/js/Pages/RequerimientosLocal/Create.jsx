import React, { useState, useEffect, useMemo } from 'react'
import { Head, usePage, useForm } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import {
  Card,
  CardHeader,
  CardBody,
  Button,
  Input,
  Select,
  SelectItem,
  Textarea,
  Divider,
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  useDisclosure,
  Checkbox,
  Chip,
  Tooltip,
  Badge,
  Dropdown,
  DropdownTrigger,
  DropdownMenu,
  DropdownItem,
} from '@heroui/react'
import { Search, Plus, Trash, List, AlertTriangle, Edit, Package, ShoppingBag } from 'lucide-react'
import debounce from 'lodash/debounce'
import toast from 'react-hot-toast'

export default function Create() {
  const { locales, productos, localAsignado } = usePage().props
  const [productosState, setProductosState] = useState([])
  const [stockMap, setStockMap] = useState({})

  // Inertia form
  const { data, setData, post, processing, errors } = useForm({
    local_id: localAsignado || '',
    observaciones: '',
    detalles: []
  })

  // Cargar stock actual desde API y fusionar con productos
  useEffect(() => {
    // Inicializa con stock 0
    setProductosState(productos.map(p => ({ ...p, stock_actual: 0 })))
    if (!localAsignado) return

    fetch(`/api/inventario-local/${localAsignado}`)
      .then(res => res.json())
      .then(stocks => {
        const mapa = stocks.reduce((m, item) => {
          m[item.producto_almacen_id] = item.cantidad
          return m
        }, {})
        setStockMap(mapa)
        setProductosState(
          productos.map(p => ({
            ...p,
            stock_actual: mapa[p.id] ?? 0
          }))
        )
      })
      .catch(() => {
        // en error, dejar stock en 0
        setProductosState(productos.map(p => ({ ...p, stock_actual: 0 })))
      })
  }, [localAsignado, productos])

  // Modal y búsqueda
  const { isOpen, onOpen, onClose } = useDisclosure()
  const [searchTerm, setSearchTerm] = useState('')
  const [filteredProductos, setFiltered] = useState(productosState)
  const [selectedProducto, setSelectedProd] = useState(null)

  // Nuevos estados para la mejora de UX
  const [multipleSeleccion, setMultipleSeleccion] = useState([])
  const [seleccionMasiva, setSeleccionMasiva] = useState(false)
  const [productosRecientes, setProductosRecientes] = useState([])
  const [categoriasFiltro, setCategoriasFiltro] = useState([])
  const [categoriaSeleccionada, setCategoriaSeleccionada] = useState('todas')

  // Extraer categorías únicas para filtrado
  useEffect(() => {
    if (productosState.length > 0) {
      const categorias = [...new Set(productosState.map(p => p.categoria || 'Sin categoría'))]
      setCategoriasFiltro(['todas', ...categorias])
    }
  }, [productosState])

  // Recuperar productos recientes del localStorage al cargar
  useEffect(() => {
    try {
      const recientes = JSON.parse(localStorage.getItem('productosRecientes') || '[]')
      setProductosRecientes(recientes)
    } catch (e) {
      console.error("Error al cargar datos locales:", e)
    }
  }, [])

  

  // Guardar producto reciente
  const guardarProductoReciente = (producto) => {
    if (!producto) return

    const nuevoRecientes = [
      { id: producto.id, nombre: producto.nombre, codigo: producto.codigo },
      ...productosRecientes.filter(p => p.id !== producto.id)
    ].slice(0, 10)

    setProductosRecientes(nuevoRecientes)
    localStorage.setItem('productosRecientes', JSON.stringify(nuevoRecientes))
  }

  // Búsqueda debounced con mejora
// 1) Al abrir el modal, cargamos todos los productos en filteredProductos
useEffect(() => {
  if (isOpen) {
    setFiltered(productosState.slice(0, 50))
  }
}, [isOpen, productosState])

// 2) Nuevo debouncedSearch: con q vacío muestra todos
const debouncedSearch = useMemo(() => debounce(q => {
  // empezamos con TODOS
  let filtrados = productosState

  // si escribieron algo, filtramos
  if (q) {
    const lq = q.toLowerCase()
    filtrados = productosState.filter(p =>
      p.nombre.toLowerCase().includes(lq) ||
      (p.codigo || '').toLowerCase().includes(lq)
    )
  }

  // aplicar filtro de categoría
  if (categoriaSeleccionada !== 'todas') {
    filtrados = filtrados.filter(p =>
      (p.categoria || 'Sin categoría') === categoriaSeleccionada
    )
  }

  // finalmente, limitamos a 50
  setFiltered(filtrados.slice(0, 50))
}, 200), [productosState, categoriaSeleccionada])


  function onSearchChange(e) {
    setSearchTerm(e.target.value)
    debouncedSearch(e.target.value)
  }

  function selectProducto(id) {
    if (seleccionMasiva) {
      toggleProductoMultiple(id)
    } else {
      const p = productosState.find(x => x.id === id)
      if (p) {
        setSelectedProd(p)
        setSearchTerm(p.nombre)
        setFiltered([])
        guardarProductoReciente(p)
      }
    }
  }

  // Selección múltiple
  function toggleProductoMultiple(id) {
    if (multipleSeleccion.includes(id)) {
      setMultipleSeleccion(multipleSeleccion.filter(x => x !== id))
    } else {
      setMultipleSeleccion([...multipleSeleccion, id])
    }
  }

  // Añadir productos en modo múltiple
  function addProductosMultiples() {
    if (multipleSeleccion.length === 0) {
      return toast.error('Selecciona al menos un producto')
    }

    const nuevosProductos = multipleSeleccion
      .filter(id => !data.detalles.some(d => d.producto_almacen_id === id))
      .map(id => {
        const prod = productosState.find(x => x.id === id)
        if (!prod) return null
        return {
          producto_almacen_id: prod.id,
          nombre_producto: prod.nombre,
          cantidad_requerida: 1,
          stock_actual: prod.stock_actual
        }
      })
      .filter(Boolean)

    if (nuevosProductos.length === 0) {
      return toast.error('Todos los productos seleccionados ya fueron agregados')
    }

    setData('detalles', [...data.detalles, ...nuevosProductos])
    toast.success(`${nuevosProductos.length} productos agregados`)
    nuevosProductos.forEach(p => guardarProductoReciente(p))
    setMultipleSeleccion([])
  }

  function addProducto() {
    if (seleccionMasiva) {
      return addProductosMultiples()
    }

    if (!selectedProducto) {
      return toast.error('Selecciona un producto')
    }

    if (data.detalles.some(d => d.producto_almacen_id === selectedProducto.id)) {
      return toast.error('Ya agregaste ese producto')
    }

    setData('detalles', [
      ...data.detalles,
      {
        producto_almacen_id: selectedProducto.id,
        nombre_producto: selectedProducto.nombre,
        cantidad_requerida: 1,
        stock_actual: selectedProducto.stock_actual
      }
    ])

    setSelectedProd(null)
    setSearchTerm('')
  }

  function removeProducto(i) {
    const arr = [...data.detalles]
    arr.splice(i, 1)
    setData('detalles', arr)
  }

  function handleSubmit(e) {
    e.preventDefault()
    if (!data.local_id) {
      return toast.error('Debes seleccionar un local')
    }
    if (data.detalles.length === 0) {
      return toast.error('Agrega al menos un producto')
    }
    const cantidadesInvalidas = data.detalles.some(d => d.cantidad_requerida < 1)
    if (cantidadesInvalidas) {
      return toast.error('Todas las cantidades deben ser mayores a cero')
    }

    post(route('requerimientos_local.store'), {
      onSuccess: () => toast.success('Requerimiento guardado'),
      onError: () => toast.error('Corrige los errores'),
    })
  }

  function updateCantidad(index, valor) {
    const newDetalles = [...data.detalles]
    newDetalles[index].cantidad_requerida = valor
    setData('detalles', newDetalles)
  }

  function limpiarTodo() {
    if (confirm('¿Estás seguro de eliminar todos los productos?')) {
      setData('detalles', [])
      toast.success('Lista limpiada')
    }
  }

  function agregarProductosPopulares() {
    toast.info('Funcionalidad en desarrollo')
  }

  function toggleModoSeleccion() {
    setSeleccionMasiva(!seleccionMasiva)
    setMultipleSeleccion([])
    setSearchTerm('')
    setFiltered([])
  }

  const isCajera = !!localAsignado
  const isAdmin = !isCajera

  return (
    <AuthenticatedLayout>
      <Head title="Crear Requerimiento" />

      <div className="p-4 space-y-6">
        <div className="flex justify-between items-center">
          <h1 className="text-2xl font-semibold">Crear Requerimiento</h1>
          <div className="flex gap-2">
            <Tooltip content="Volver atrás">
              <Button variant="flat" onPress={() => history.back()}>Volver</Button>
            </Tooltip>
          </div>
        </div>

        <Card className="shadow-md">
          <CardHeader className="bg-blue-50">
            <h2 className="text-lg font-semibold text-blue-700">Información del Requerimiento</h2>
          </CardHeader>
          <CardBody>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {isAdmin ? (
                  <Select
                    label="Local"
                    value={data.local_id}
                    onChange={val => setData('local_id', val)}
                    status={errors.local_id ? 'error' : 'default'}
                    isRequired
                    className="bg-white"
                  >
                    {locales.map(l => (
                      <SelectItem key={l.id} value={l.id}>
                        {l.nombre_local}
                      </SelectItem>
                    ))}
                  </Select>
                ) : (
                  <Input
                    label="Local"
                    value={localAsignado && (locales.find(l => l.id === localAsignado)?.nombre_local || '')}
                    readOnly
                    className="bg-gray-50"
                  />
                )}

                <Textarea
                  label="Observaciones"
                  value={data.observaciones}
                  onChange={e => setData('observaciones', e.target.value)}
                  status={errors.observaciones ? 'error' : 'default'}
                  placeholder="Indicaciones adicionales sobre este requerimiento..."
                  className="bg-white"
                />
              </div>

              <Divider className="my-4" />

              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-medium text-gray-800">Productos del Requerimiento</h3>
                {data.detalles.length > 0 && (
                  <Button
                    variant="flat"
                    color="danger"
                    size="sm"
                    startContent={<Trash className="h-4 w-4" />}
                    onPress={limpiarTodo}
                  >
                    Limpiar Lista
                  </Button>
                )}
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="flex flex-col gap-2">
                  <div className="flex gap-2">
                    <Button
                      variant="flat"
                      color="primary"
                      startContent={<Search className="h-5 w-5" />}
                      onPress={onOpen}
                      className="flex-grow"
                    >
                      Buscar Productos
                    </Button>
                  </div>

                  {productosRecientes.length > 0 && (
                    <div className="flex flex-wrap gap-2 mt-2">
                      <span className="text-xs text-gray-500">Recientes:</span>
                      {productosRecientes.slice(0, 5).map(p => (
                        <Chip
                          key={p.id}
                          variant="flat"
                          color="primary"
                          size="sm"
                          className="cursor-pointer"
                          onClick={() => selectProducto(p.id)}
                        >
                          {p.nombre}
                        </Chip>
                      ))}
                    </div>
                  )}
                </div>

                <div>
                  {selectedProducto && !seleccionMasiva && (
                    <div className="p-3 bg-blue-50 rounded-lg border border-blue-200 flex justify-between items-center animate-fadeIn">
                      <div>
                        <div className="font-semibold text-blue-700">{selectedProducto.nombre}</div>
                        {selectedProducto.codigo && (
                          <div className="text-sm text-blue-600">#{selectedProducto.codigo}</div>
                        )}
                        <div className="text-xs text-gray-600">
                          Stock actual: {selectedProducto.stock_actual} unidades
                        </div>
                      </div>
                      <Button
                        color="primary"
                        startContent={<Plus className="h-5 w-5" />}
                        onPress={addProducto}
                      >
                        Agregar
                      </Button>
                    </div>
                  )}

                  {seleccionMasiva && multipleSeleccion.length > 0 && (
                    <div className="p-3 bg-purple-50 rounded-lg border border-purple-200 flex justify-between items-center">
                      <div>
                        <div className="font-semibold text-purple-700">
                          {multipleSeleccion.length} productos seleccionados
                        </div>
                      </div>
                      <Button
                        color="primary"
                        startContent={<Plus className="h-5 w-5" />}
                        onPress={addProductosMultiples}
                      >
                        Agregar Seleccionados
                      </Button>
                    </div>
                  )}
                </div>
              </div>

              {data.detalles.length > 0 ? (
                <div className="bg-white border rounded-lg overflow-hidden">
                  <Table aria-label="Tabla de detalles del requerimiento">
                    <TableHeader>
                      <TableColumn>Producto</TableColumn>
                      <TableColumn>Stock Actual</TableColumn>
                      <TableColumn width={80}>Acciones</TableColumn>
                    </TableHeader>
                    <TableBody>
                      {data.detalles.map((d, i) => (
                        <TableRow key={i} className="hover:bg-gray-50">
                          <TableCell>
                            <div>
                              <div className="font-medium">{d.nombre_producto}</div>
                              {d.codigo && <div className="text-xs text-gray-500">#{d.codigo}</div>}
                            </div>
                          </TableCell>
                          <TableCell>
                            <Badge color={d.stock_actual > 5 ? "success" : "warning"} variant="flat">
                              {d.stock_actual || 0} unid.
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <Button
                              isIconOnly
                              color="danger"
                              variant="light"
                              size="sm"
                              onPress={() => removeProducto(i)}
                              title="Eliminar"
                            >
                              <Trash className="h-4 w-4" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              ) : (
                <div className="bg-yellow-50 text-yellow-800 p-4 rounded-lg border border-yellow-200 flex items-center gap-3">
                  <AlertTriangle className="h-5 w-5" />
                  <span>No hay productos en el requerimiento. Utiliza el buscador para agregar productos.</span>
                </div>
              )}

              <div className="flex justify-between items-center pt-4">
                <div className="text-sm text-gray-600">
                  {data.detalles.length} productos en total
                </div>
                <Button
                  color="primary"
                  type="submit"
                  isDisabled={processing || data.detalles.length === 0}
                  size="lg"
                  className="px-8"
                >
                  Guardar Requerimiento
                </Button>
              </div>
            </form>
          </CardBody>
        </Card>
      </div>

      <Modal isOpen={isOpen} onOpenChange={onClose} size="4xl" scrollBehavior="inside">
        <ModalContent>
          {onCloseInner => (
            <>
              <ModalHeader className="flex justify-between items-center">
                <h2 className="text-lg font-semibold">Selección Múltiple de Productos</h2>
              </ModalHeader>

              <ModalBody>
                <div className="flex gap-3 flex-col md:flex-row mb-4">
                  <Input
                    placeholder="Buscar por nombre o código..."
                    value={searchTerm}
                    onChange={onSearchChange}
                    startContent={<Search className="h-5 w-5" />}
                    clearable
                    autoFocus
                    className="bg-white flex-grow"
                  />
                  {categoriasFiltro.length > 1 && (
                    <Select
                      placeholder="Filtrar por categoría"
                      value={categoriaSeleccionada}
                      onChange={setCategoriaSeleccionada}
                      className="min-w-[180px]"
                    >
                      {categoriasFiltro.map(cat => (
                        <SelectItem key={cat} value={cat}>
                          {cat === 'todas' ? 'Todas las categorías' : cat}
                        </SelectItem>
                      ))}
                    </Select>
                  )}
                </div>

                {productosRecientes.length > 0 && (
                  <div className="mb-4">
                    <h4 className="text-sm font-medium mb-2">Productos recientes:</h4>
                    <div className="flex flex-wrap gap-2">
                      {productosRecientes.map(p => (
                        <Chip
                          key={p.id}
                          variant="flat"
                          color="primary"
                          className="cursor-pointer"
                          onClick={() => toggleProductoMultiple(p.id)}
                        >
                          {p.nombre}
                        </Chip>
                      ))}
                    </div>
                  </div>
                )}

                <div className="max-h-[400px] overflow-auto">
                  {multipleSeleccion.length > 0 && (
                    <div className="bg-purple-50 p-3 rounded-lg mb-3 flex justify-between items-center">
                      <span>{multipleSeleccion.length} productos seleccionados</span>
                      <div className="flex gap-2">
                        <Button size="sm" color="secondary" onPress={() => setMultipleSeleccion([])}>
                          Limpiar selección
                        </Button>
                        <Button
                          size="sm"
                          color="primary"
                          onPress={() => {
                            addProductosMultiples()
                            onCloseInner()
                          }}
                        >
                          Agregar seleccionados
                        </Button>
                      </div>
                    </div>
                  )}

                  {filteredProductos.length > 0 ? (
                    <Table aria-label="resultados" className="min-w-full">
                      <TableHeader>
                        <TableColumn width={50} isRowHeader={false} />
                        <TableColumn isRowHeader>Nombre</TableColumn>
                        <TableColumn width={100} isRowHeader={false}>Código</TableColumn>
                        <TableColumn width={100} isRowHeader={false}>Stock</TableColumn>
                      </TableHeader>
                      <TableBody
                        emptyContent={
                          searchTerm
                            ? 'No se encontraron resultados'
                            : 'Escribe para buscar productos'
                        }
                      >
                        {filteredProductos.map(p => (
                          <TableRow key={p.id} className="hover:bg-gray-50">
                            <TableCell>
                              <Checkbox
                                isSelected={multipleSeleccion.includes(p.id)}
                                onChange={() => toggleProductoMultiple(p.id)}
                              />
                            </TableCell>
                            <TableCell>{p.nombre}</TableCell>
                            <TableCell>{p.codigo || '–'}</TableCell>
                            <TableCell>
                              <Badge color={p.stock_actual > 5 ? 'success' : 'warning'} variant="flat">
                                {p.stock_actual || 0}
                              </Badge>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <div className="text-center py-10 text-gray-500 bg-gray-50 rounded-lg">
                      {searchTerm ? 'No se encontraron resultados' : 'Escribe para buscar productos'}
                    </div>
                  )}
                </div>
              </ModalBody>

              <ModalFooter>
                <Button
                  color="primary"
                  onPress={() => {
                    addProductosMultiples()
                    onCloseInner()
                  }}
                  className="mr-auto"
                >
                  Agregar {multipleSeleccion.length} productos
                </Button>
                <Button variant="light" onPress={() => onCloseInner()}>
                  Cerrar
                </Button>
              </ModalFooter>
            </>
          )}
        </ModalContent>
      </Modal>
    </AuthenticatedLayout>
  )
}
