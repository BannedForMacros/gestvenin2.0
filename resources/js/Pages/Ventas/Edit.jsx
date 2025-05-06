// resources/js/Pages/Ventas/Edit.jsx
import React, { useState, useMemo, useEffect } from 'react'
import { Head, usePage, router } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import {
  Card,
  CardHeader,
  CardBody,
  Input,
  Button,
  Select,
  SelectItem,
  Divider,
  Chip,
  Tabs,
  Tab,
  Progress,
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  useDisclosure,
  Switch,
} from '@heroui/react'
import {
  ShoppingCartIcon,
  CreditCardIcon,
  MagnifyingGlassIcon,
  TruckIcon,
  CheckIcon,
  GiftIcon,
  TrashIcon,
  PlusCircleIcon,
  CubeIcon,
  BanknotesIcon,
  QrCodeIcon,
  ArrowPathIcon,
  ExclamationCircleIcon,
  UserIcon,
  PhoneIcon,
} from '@heroicons/react/24/outline'
import { CheckCircleIcon } from '@heroicons/react/24/solid'

export default function Edit() {
  const { venta, productos, metodosPago, auth, errors } = usePage().props
  const isCajera = auth.roles.includes('cajera')
  const { isOpen, onOpen, onClose } = useDisclosure()

  // --- 1) Inicializar estados ---
  const [selectedProducts, setSelectedProducts] = useState(() =>
    venta.detalles.map(d => {
      const prod = productos.find(p => p.id === d.producto_id) || {}
      return {
        id:               prod.id,
        nombre:           prod.nombre,
        precio_normal:    Number(prod.precio) || 0,
        precio_delivery:  Number(prod.precio_delivery) || 0,
        precio:           venta.es_delivery && prod.precio_delivery > 0
                          ? Number(prod.precio_delivery)
                          : Number(prod.precio),
        cantidad:         d.cantidad,
        cortesia:         d.cortesia === 'si',
        categoria:        prod.categoria || 'sin categoría',
      }
    })
  )
  const [payments, setPayments] = useState(
    venta.pagos.map(p => ({ metodo: p.metodo_pago, monto: p.monto }))
  )
  const [esDelivery, setEsDelivery] = useState(!!venta.es_delivery)
  const [cliente, setCliente] = useState({
    nombre:    venta.delivery?.nombre_cliente    || '',
    direccion: venta.delivery?.direccion_cliente || '',
    numero:    venta.delivery?.numero_cliente    || '',
  })
  const [searchTerm, setSearchTerm] = useState('')
  const [categoriaSeleccionada, setCategoriaSeleccionada] = useState('todos')
  const [filteredProducts, setFilteredProducts] = useState(productos)
  const [paymentProgress, setPaymentProgress] = useState(0)
  const [showSuccess, setShowSuccess] = useState(false)

  // --- 2) Categorías únicas ---
  const categorias = useMemo(() => {
    const cats = [...new Set(productos.map(p => p.categoria || 'sin categoría'))]
    return ['todos', ...cats]
  }, [productos])

  // --- 3) Ajustar precios al togglear delivery ---
  useEffect(() => {
    setSelectedProducts(ps =>
      ps.map(p => ({
        ...p,
        precio: esDelivery && p.precio_delivery > 0
                ? p.precio_delivery
                : p.precio_normal
      }))
    )
  }, [esDelivery])

  // --- 4) Calcular subtotal, costoDelivery y total ---
  const subtotal = useMemo(
    () => selectedProducts.reduce((sum, p) => sum + p.precio_normal * p.cantidad, 0),
    [selectedProducts]
  )
  const costoDelivery = useMemo(
    () => selectedProducts.reduce((sum, p) => sum + p.precio_delivery * p.cantidad, 0),
    [selectedProducts]
  )
  const total = useMemo(
    () => esDelivery ? subtotal + costoDelivery : subtotal,
    [subtotal, costoDelivery, esDelivery]
  )

  // --- 5) Progreso de pagos ---
  const totalPagado = useMemo(
    () => payments.reduce((sum, p) => sum + Number(p.monto), 0),
    [payments]
  )
  useEffect(() => {
    setPaymentProgress(total > 0 ? Math.min((totalPagado / total) * 100, 100) : 0)
  }, [total, totalPagado])

  // --- 6) Filtrar catálogo ---
  useEffect(() => {
    let fp = productos
    if (searchTerm) {
      fp = fp.filter(p => p.nombre.toLowerCase().includes(searchTerm.toLowerCase()))
    }
    if (categoriaSeleccionada !== 'todos') {
      fp = fp.filter(p => (p.categoria || 'sin categoría') === categoriaSeleccionada)
    }
    setFilteredProducts(fp)
  }, [searchTerm, categoriaSeleccionada, productos])

  // --- 7) Handlers de productos ---
  function addProduct(prod) {
    setSelectedProducts(ps => {
      const exists = ps.find(p => p.id === prod.id)
      if (exists) {
        return ps.map(p =>
          p.id === prod.id ? { ...p, cantidad: p.cantidad + 1 } : p
        )
      }
      return [
        ...ps,
        {
          id:               prod.id,
          nombre:           prod.nombre,
          precio_normal:    Number(prod.precio) || 0,
          precio_delivery:  Number(prod.precio_delivery) || 0,
          precio:           esDelivery && prod.precio_delivery > 0
                            ? Number(prod.precio_delivery)
                            : Number(prod.precio),
          cantidad:         1,
          cortesia:         false,
          categoria:        prod.categoria || 'sin categoría',
        },
      ]
    })
  }
  function updateQuantity(i, v) {
    setSelectedProducts(ps =>
      ps.map((p, idx) => idx === i ? { ...p, cantidad: Number(v) } : p)
    )
  }
  function toggleCortesia(i) {
    setSelectedProducts(ps =>
      ps.map((p, idx) => idx === i ? { ...p, cortesia: !p.cortesia } : p)
    )
  }
  function removeProduct(i) {
    setSelectedProducts(ps => ps.filter((_, idx) => idx !== i))
  }

  // --- 8) Handlers de pagos ---
  function addPayment() {
    const pendiente = Math.max(0, total - totalPagado)
    setPayments(ps => [...ps, { metodo: 'efectivo', monto: pendiente }])
  }
  function updatePayment(i, key, val) {
    setPayments(ps =>
      ps.map((p, idx) =>
        idx === i ? { ...p, [key]: key === 'monto' ? Number(val) : val } : p
      )
    )
  }
  function removePayment(i) {
    setPayments(ps => ps.filter((_, idx) => idx !== i))
  }
  function balancearPago() {
    if (!payments.length) return
    const pendiente = Math.max(
      0,
      total - payments.slice(0, -1).reduce((sum, p) => sum + p.monto, 0)
    )
    updatePayment(payments.length - 1, 'monto', pendiente)
  }

  // --- 9) Envío del formulario ---
  function handleSubmit(e) {
    e.preventDefault()
    if (!selectedProducts.length) {
      alert('Seleccione al menos un producto')
      return
    }
    if (Math.abs(totalPagado - total) > 0.01) {
      onOpen()
      return
    }
    submitVenta()
  }
  function submitVenta() {
    onClose()
    const data = {
      fecha_venta:   venta.fecha_venta,
      local_id:      venta.local_id,             // <<–– lo enviamos oculto
      productos:     selectedProducts.map(p => ({
                       producto_id: p.id,
                       cantidad:    p.cantidad,
                       cortesia:    p.cortesia ? 'si' : ''
                     })),
      pagos:         payments,
      ...(esDelivery ? { es_delivery: 1 } : {}),
      total:         total.toFixed(2),           // <<–– lo enviamos oculto
    }
    if (esDelivery) {
      data.nombre_cliente    = cliente.nombre
      data.direccion_cliente = cliente.direccion
      data.numero_cliente    = cliente.numero
    }
    setShowSuccess(true)
    setTimeout(() => {
      router.put(route('ventas.update', venta.id), data)
    }, 500)
  }

  // --- 10) Iconos de pago ---
  function getPaymentIcon(m) {
    switch (m) {
      case 'efectivo':  return <BanknotesIcon className="h-4 w-4 text-green-600"/>
      case 'yape':      return <QrCodeIcon    className="h-4 w-4 text-purple-600"/>
      case 'plin':      return <QrCodeIcon    className="h-4 w-4 text-purple-600"/>
      case 'pedidosya': return <TruckIcon     className="h-4 w-4 text-red-500"/>
      default:          return <CreditCardIcon className="h-4 w-4 text-blue-600"/>
    }
  }

  return (
    <AuthenticatedLayout>
      <Head title="Editar Venta" />

      {/* Overlay de éxito */}
      {showSuccess && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
          <div className="bg-white p-8 rounded-xl shadow-2xl animate-pulse flex flex-col items-center">
            <CheckCircleIcon className="h-16 w-16 text-green-500 mb-3"/>
            <h2 className="text-2xl font-bold">Actualizando venta...</h2>
          </div>
        </div>
      )}

      {/* Modal: pagos no coinciden */}
      <Modal isOpen={isOpen} onClose={onClose} backdrop="blur">
        <ModalContent>
          <ModalHeader>
            <div className="flex items-center gap-2 text-amber-700">
              <ExclamationCircleIcon className="h-6 w-6"/> Pagos no coinciden
            </div>
          </ModalHeader>
          <ModalBody>
            <p>Total: S/ {total.toFixed(2)}</p>
            <p>Pagado: S/ {totalPagado.toFixed(2)}</p>
          </ModalBody>
          <ModalFooter>
            <Button color="danger" variant="light" onPress={onClose}>
              Cancelar
            </Button>
            <Button color="primary" onPress={submitVenta}>
              Continuar
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* --- Campos ocultos obligatorios para tu update() --- */}
        <Input type="hidden" name="local_id" value={venta.local_id} />
        <Input type="hidden" name="total"    value={total.toFixed(2)} />

        {/* Cabecera */}
        <div className="flex justify-between items-center bg-gradient-to-r from-blue-600 to-indigo-700 p-4 rounded-xl text-white">
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <ShoppingCartIcon className="h-7 w-7"/> Editar Venta
          </h1>
          <Button type="submit" color="success" startContent={<CheckIcon/>}>
            Guardar — S/ {total.toFixed(2)}
          </Button>
        </div>

        {/* Buscador */}
        <div className="flex justify-end">
          <Input
            placeholder="Buscar..."
            startContent={<MagnifyingGlassIcon className="h-5 w-5 text-gray-400"/>}
            value={searchTerm}
            onChange={e => setSearchTerm(e.target.value)}
            className="w-64"
          />
        </div>

        <div className="grid lg:grid-cols-12 gap-6">
          {/* ====================== IZQUIERDA ====================== */}
          <div className="lg:col-span-8 space-y-5">
            {/* Productos Seleccionados */}
            <Card radius="lg">
              <CardHeader className="bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
                Productos Seleccionados
              </CardHeader>
              <CardBody className="p-0">
                <div className="overflow-y-auto h-72">
                  <table className="w-full text-sm">
                    <thead className="bg-gray-100">
                      <tr>
                        <th className="p-3 text-left">Producto</th>
                        <th className="p-3 w-24 text-center">Cant.</th>
                        <th className="p-3 w-28 text-center">Precio</th>
                        <th className="p-3 w-28 text-center">Subtotal</th>
                        <th className="p-3 w-28 text-center">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      {selectedProducts.length === 0 ? (
                        <tr>
                          <td colSpan="5" className="p-4 text-center text-gray-500">
                            No hay productos seleccionados
                          </td>
                        </tr>
                      ) : selectedProducts.map((p,i)=>(
                        <tr key={i}
                          className={`border-b hover:bg-gray-50 transition-colors ${
                            p.cortesia ? 'bg-green-50 hover:bg-green-100' : ''
                          }`}
                        >
                          <td className="p-3 flex items-center gap-2">
                            <CubeIcon className="h-5 w-5 text-blue-500"/>
                            <div>
                              <div className="font-medium">{p.nombre}</div>
                              <div className="text-xs text-gray-500">{p.categoria}</div>
                            </div>
                          </td>
                          <td className="p-3 text-center">
                            <Input
                              type="number"
                              value={p.cantidad}
                              min={1}
                              size="sm"
                              className="w-16 text-center"
                              onValueChange={v=>updateQuantity(i,v)}
                            />
                          </td>
                          <td className="p-3 text-center">
                            <div className="flex flex-col">
                              <span className="font-medium">S/ {p.precio_normal.toFixed(2)}</span>
                              {esDelivery && p.precio_delivery>0 && (
                                <span className="text-xs text-green-600">
                                  Precio delivery: S/ {p.precio_delivery.toFixed(2)}
                                </span>
                              )}
                            </div>
                          </td>
                          <td className="p-3 text-center font-semibold">
                            S/ {(
                              esDelivery
                                ? (p.precio_normal + p.precio_delivery) * p.cantidad
                                : p.precio_normal * p.cantidad
                            ).toFixed(2)}
                          </td>
                          <td className="p-3 text-center">
                            <div className="flex justify-center gap-2">
                              <Button
                                size="sm" isIconOnly
                                variant={p.cortesia?'solid':'flat'}
                                color={p.cortesia?'success':'primary'}
                                onPress={()=>toggleCortesia(i)}
                              >
                                <GiftIcon className="h-4 w-4"/>
                              </Button>
                              <Button
                                size="sm" isIconOnly variant="flat" color="danger"
                                onPress={()=>removeProduct(i)}
                              >
                                <TrashIcon className="h-4 w-4"/>
                              </Button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                {/* Resumen */}
                {selectedProducts.length>0 && (
                  <>
                    <Divider className="my-3"/>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="bg-gray-50 p-3 rounded-lg">
                        <div className="flex justify-between mb-1">
                          <span>Subtotal:</span>
                          <span>S/ {subtotal.toFixed(2)}</span>
                        </div>
                        {esDelivery && (
                          <div className="flex justify-between mb-1">
                            <span>Costo de delivery:</span>
                            <span>S/ {costoDelivery.toFixed(2)}</span>
                          </div>
                        )}
                        <Divider className="my-2"/>
                        <div className="flex justify-between font-bold">
                          <span>TOTAL:</span>
                          <span>S/ {total.toFixed(2)}</span>
                        </div>
                      </div>
                      <div className="bg-blue-50 p-3 rounded-lg">
                        <div className="flex justify-between mb-1">
                          <span>Total pagado:</span>
                          <span className={`font-semibold ${
                            Math.abs(totalPagado-total)>0.01
                              ? (totalPagado>total?'text-green-600':'text-red-600')
                              : 'text-green-600'
                          }`}>
                            S/ {totalPagado.toFixed(2)}
                          </span>
                        </div>
                        <Progress
                          value={paymentProgress}
                          color={
                            paymentProgress>=100?'success'
                            :paymentProgress>80?'primary'
                            :'warning'
                          }
                          className="h-2"
                        />
                        <div className="flex justify-between mt-2">
                          <span className="text-sm">
                            {Math.abs(totalPagado-total)>0.01
                              ? (totalPagado>total
                                  ? `Sobra: S/ ${(totalPagado-total).toFixed(2)}`
                                  : `Falta: S/ ${(total-totalPagado).toFixed(2)}`)
                              : 'Pago completo'
                            }
                          </span>
                          <Button
                            size="sm" variant="flat" color="primary"
                            startContent={<ArrowPathIcon className="h-4 w-4"/>}
                            onPress={balancearPago}
                          >
                            Balancear
                          </Button>
                        </div>
                      </div>
                    </div>
                  </>
                )}
              </CardBody>
            </Card>

{/* — Métodos de Pago Card — */}
<Card radius="lg">
  <CardHeader className="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
    Métodos de Pago
  </CardHeader>
  <CardBody className="space-y-2">
    {payments.map((pay, i) => {
      // 1) todos los métodos
      const allMethods = ["efectivo","yape","plin","tarjeta","pedidosya","yape2"];
      // 2) cuáles ya están en uso en las otras filas
      const used = payments
        .filter((_, j) => j !== i)
        .map((p, j) =>
          // fila 0 siempre reserva "efectivo" si no hay nada
          j === 0 ? (payments[0].metodo || "efectivo") : p.metodo
        )
        .filter(Boolean);
      // 3) el valor real a mostrar (fila0 = Efectivo por defecto)
      const display = pay.metodo || (i === 0 ? "efectivo" : "");
      // 4) sólo métodos no usados (o mi propio valor)
      const available = allMethods.filter(m => m === display || !used.includes(m));

      return (
        <div key={i} className="flex items-center gap-2">
          {/* — SELECT DE MÉTODO — */}
          <Select
            placeholder="Seleccione método…"
            size="sm"
            fullWidth
            startContent={getPaymentIcon(display)}
            selectedKeys={display ? [display] : []}
            onSelectionChange={keys => {
              const next = Array.from(keys)[0] || "";
              updatePayment(i, "metodo", next);
            }}
          >
            {/* placeholder como opción deshabilitada */}
            <SelectItem key="" isDisabled textValue="Seleccione método…">
              Seleccione método…
            </SelectItem>
            {available.map(m => {
              const label = m.charAt(0).toUpperCase() + m.slice(1);
              return (
                <SelectItem key={m} value={m} textValue={label}>
                  {label}
                </SelectItem>
              );
            })}
          </Select>

          {/* — INPUT DE MONTO — */}
          <Input
            type="number"
            step="0.01"
            size="sm"
            startContent={<span className="text-gray-500">S/</span>}
            value={pay.monto}
            onValueChange={v => updatePayment(i, "monto", v)}
            className="w-24"
          />

          {/* — BOTÓN BORRAR FILA — */}
          {payments.length > 1 && (
            <Button
              isIconOnly
              size="sm"
              variant="flat"
              color="danger"
              onPress={() => removePayment(i)}
              title="Eliminar método"
            >
              <TrashIcon className="h-4 w-4" />
            </Button>
          )}
        </div>
      );
    })}

    {/* — AGREGAR NUEVA FILA — */}
    <div className="text-right">
      <Button
        size="sm"
        variant="flat"
        color="secondary"
        startContent={<PlusCircleIcon className="h-4 w-4" />}
        onPress={addPayment}
      >
        Agregar Método
      </Button>
    </div>
  </CardBody>
</Card>

          </div>

          {/* ====================== DERECHA ====================== */}
          <div className="lg:col-span-4 space-y-5">
            {/* Catálogo */}
            <Card radius="lg">
              <CardHeader className="bg-gradient-to-r from-green-600 to-emerald-600 text-white">
                Catálogo de Productos
              </CardHeader>
              <CardBody>
                <Input
                  placeholder="Buscar..." fullWidth variant="bordered" size="sm"
                  startContent={<MagnifyingGlassIcon className="h-4 w-4"/>}
                  value={searchTerm}
                  onValueChange={setSearchTerm}
                  className="mb-2"
                />
                <div className="flex gap-1 overflow-x-auto pb-2">
                  {categorias.map(cat=>(
                    <Chip
                      key={cat}
                      variant={categoriaSeleccionada===cat?'solid':'flat'}
                      color={categoriaSeleccionada===cat?'primary':'default'}
                      className="capitalize"
                      onClick={()=>setCategoriaSeleccionada(cat)}
                    >
                      {cat}
                    </Chip>
                  ))}
                </div>
                <Tabs variant="underlined" color="success">
                  <Tab title="Cuadrícula">
                    <div className="grid grid-cols-2 gap-2 mt-2 h-64 overflow-y-auto">
                      {filteredProducts.map(p=>(
                        <div
                          key={p.id}
                          className="border rounded-lg p-2 cursor-pointer hover:bg-green-50"
                          onClick={()=>addProduct(p)}
                        >
                          <div className="truncate font-medium">{p.nombre}</div>
                          <div className="text-green-600">
                            S/ {esDelivery && p.precio_delivery>0
                              ? p.precio_delivery.toFixed(2)
                              : Number(p.precio).toFixed(2)
                            }
                          </div>
                        </div>
                      ))}
                    </div>
                  </Tab>
                  <Tab title="Lista">
                    <ul className="divide-y divide-gray-100 h-64 overflow-y-auto">
                      {filteredProducts.map(p=>(
                        <li
                          key={p.id}
                          className="p-2 flex justify-between items-center hover:bg-green-50 cursor-pointer"
                          onClick={()=>addProduct(p)}
                        >
                          <span>{p.nombre}</span>
                          <span className="text-green-600">
                            S/ {esDelivery && p.precio_delivery>0
                              ? p.precio_delivery.toFixed(2)
                              : Number(p.precio).toFixed(2)
                            }
                          </span>
                        </li>
                      ))}
                    </ul>
                  </Tab>
                </Tabs>
              </CardBody>
            </Card>

            {/* Opciones Delivery */}
            <Card radius="lg">
              <CardHeader className="bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white">
                Opciones de Delivery
              </CardHeader>
              <CardBody>
                <div className="flex justify-between items-center">
                  <span className="font-medium">Habilitar Delivery</span>
                  <Switch
                    checked={esDelivery}
                    onChange={e => setEsDelivery(e.target.checked)}
                  />
                  {esDelivery && (
                    <Chip variant="flat">+S/ {costoDelivery.toFixed(2)}</Chip>
                  )}
                </div>
                {esDelivery && (
                  <div className="space-y-3 pt-3 border-t border-gray-200">
                    <Input
                      label="Nombre Cliente"
                      fullWidth
                      variant="bordered"
                      isRequired={true}
                      value={cliente.nombre}
                      onValueChange={v => setCliente(c => ({ ...c, nombre: v }))}
                      startContent={<UserIcon className="h-4 w-4 text-purple-600"/>}
                      errorMessage={errors.nombre_cliente}
                      size="sm"
                    />
                    <Input
                      label="Dirección Cliente"
                      fullWidth
                      variant="bordered"
                      isRequired={true}
                      value={cliente.direccion}
                      onValueChange={v => setCliente(c => ({ ...c, direccion: v }))}
                      startContent={<TruckIcon className="h-4 w-4 text-purple-600"/>}
                      errorMessage={errors.direccion_cliente}
                      size="sm"
                    />
                    <Input
                      label="Teléfono Cliente"
                      fullWidth
                      variant="bordered"
                      isRequired={true}
                      value={cliente.numero}
                      onValueChange={v => setCliente(c => ({ ...c, numero: v }))}
                      startContent={<PhoneIcon className="h-4 w-4 text-purple-600"/>}
                      errorMessage={errors.numero_cliente}
                      size="sm"
                    />
                  </div>
                )}
              </CardBody>
            </Card>
          </div>
        </div>
      </form>
    </AuthenticatedLayout>
  )
}
