// resources/js/Pages/Ventas/Create.jsx
import React, { useState, useMemo, useEffect } from "react";
import { Head, usePage, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {
  Card,
  CardHeader,
  CardBody,
  Input,
  Button,
  Select,
  SelectItem,
  Checkbox,
  Divider,
  Chip,
  Tooltip,
  Badge,
  Tabs,
  Tab,
  Progress,
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  useDisclosure,
} from "@heroui/react";
import {
  ShoppingCartIcon,
  CreditCardIcon,
  MagnifyingGlassIcon,
  TruckIcon,
  CalendarIcon,
  HomeIcon,
  CheckIcon,
  GiftIcon,
  TrashIcon,
  PlusCircleIcon,
  CubeIcon,
  BanknotesIcon,
  QrCodeIcon,
  ArrowPathIcon,
  ReceiptPercentIcon,
  UserIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
} from "@heroicons/react/24/outline";
import { CheckCircleIcon } from "@heroicons/react/24/solid";

export default function Create() {
  const { productos, locales, auth, fechaActual } = usePage().props;
  const isCajera = auth.roles.includes("cajera");
  const { isOpen, onOpen, onClose } = useDisclosure();

  /* ------------------------------------------------------------------ */
  /* ====================== ESTADOS PRINCIPALES ======================= */
  /* ------------------------------------------------------------------ */
  const [selectedProducts, setSelectedProducts] = useState([]);     // carrito
  const [payments, setPayments]         = useState([{ metodo: "efectivo", monto: 0 }]);
  const [esDelivery, setEsDelivery]     = useState(false);
  const [cliente, setCliente]           = useState({ nombre: "", direccion: "", numero: "" });
  const [localId, setLocalId]           = useState(isCajera ? String(auth.user.local_id) : "");
  const [fechaVenta, setFechaVenta]     = useState(fechaActual);
  const [searchTerm, setSearchTerm]     = useState("");
  const [categoriaSeleccionada, setCategoriaSeleccionada] = useState("todos");
  const [filteredProducts, setFilteredProducts]         = useState(productos);
  const [paymentProgress, setPaymentProgress]           = useState(0);
  const [showSuccess, setShowSuccess]                   = useState(false);

  /* ------------------------- CATEGORÍAS ---------------------------- */
  const categorias = useMemo(() => {
    const cats = [...new Set(productos.map(p => p.categoria || "sin categoría"))];
    return ["todos", ...cats];
  }, [productos]);

  /* ------------------------------------------------------------------ */
  /* ================= CALCULOS DINÁMICOS DE $$$ ====================== */
  /* ------------------------------------------------------------------ */

  // 1. Sub-total (precio normal)
  const subtotal = useMemo(() => {
    return selectedProducts.reduce(
      (acc, p) => acc + (p.cortesia ? 0 : Number(p.precio) * p.cantidad),
      0
    );
  }, [selectedProducts]);

  // 2. Costo de delivery basado en precio_delivery
  const deliveryCost = useMemo(() => {
    if (!esDelivery) return 0;
    return selectedProducts.reduce(
      (acc, p) => acc + Number(p.precio_delivery || 0) * p.cantidad,
      0
    );
  }, [selectedProducts, esDelivery]);

  // 3. Total final
  const total = subtotal + deliveryCost;

  // 4. Total pagado
  const totalPagado = useMemo(
    () => payments.reduce((acc, p) => acc + Number(p.monto), 0),
    [payments]
  );

  /* ------------------ Progreso de pago (barra) ---------------------- */
  useEffect(() => {
    if (total > 0) setPaymentProgress(Math.min((totalPagado / total) * 100, 100));
    else           setPaymentProgress(0);
  }, [total, totalPagado]);

  /* ------------------------------------------------------------------ */
  /* ================== FILTRO DE PRODUCTOS =========================== */
  /* ------------------------------------------------------------------ */
  useEffect(() => {
    let filtered = productos;

    if (searchTerm)
      filtered = filtered.filter(p =>
        p.nombre.toLowerCase().includes(searchTerm.toLowerCase())
      );

    if (categoriaSeleccionada !== "todos")
      filtered = filtered.filter(
        p => (p.categoria || "sin categoría") === categoriaSeleccionada
      );

    setFilteredProducts(filtered);
  }, [searchTerm, categoriaSeleccionada, productos]);

  /* ------------------------------------------------------------------ */
  /* ==================== MANEJO DEL CARRITO ========================== */
  /* ------------------------------------------------------------------ */
  const addProduct = prod => {
    const precioNormal   = Number(prod.precio)           || 0;
    const precioDelivery = Number(prod.precio_delivery)  || 0;

    setSelectedProducts(prev => {
      const exists = prev.find(p => p.id === prod.id);

      if (exists) {
        return prev.map(p =>
          p.id === prod.id
            ? { ...p, cantidad: p.cantidad + 1 }
            : p
        );
      }

      return [
        ...prev,
        {
          id:               prod.id,
          nombre:           prod.nombre,
          precio:           precioNormal,   // precio normal para mostrar y subtotal
          precio_delivery:  precioDelivery, // adicional para delivery
          cantidad:         1,
          cortesia:         false,
          categoria:        prod.categoria || "sin categoría",
        },
      ];
    });
  };

  const updateQuantity = (idx, qty) =>
    setSelectedProducts(prev =>
      prev.map((p, i) => (i === idx ? { ...p, cantidad: Number(qty) } : p))
    );

  const toggleCortesia = idx =>
    setSelectedProducts(prev =>
      prev.map((p, i) => (i === idx ? { ...p, cortesia: !p.cortesia } : p))
    );

  const removeProduct = idx =>
    setSelectedProducts(prev => prev.filter((_, i) => i !== idx));

  /* ------------------------------------------------------------------ */
  /* =================== MANEJO DE PAGOS ============================== */
  /* ------------------------------------------------------------------ */
  const addPayment = () =>
    setPayments(prev => [...prev, { metodo: "efectivo", monto: Math.max(0, total - totalPagado) }]);

  const updatePayment = (idx, key, val) =>
    setPayments(prev =>
      prev.map((p, i) =>
        i === idx ? { ...p, [key]: key === "monto" ? Number(val) : val } : p
      )
    );

  const removePayment = idx =>
    setPayments(prev => prev.filter((_, i) => i !== idx));

  const balancearPago = () => {
    if (payments.length === 0) return;
    const pagadoExceptLast = payments
      .slice(0, -1)
      .reduce((acc, p) => acc + Number(p.monto), 0);
    updatePayment(payments.length - 1, "monto", Math.max(0, total - pagadoExceptLast));
  };

  /* ------------------------------------------------------------------ */
  /* =============== ENVÍO (submit del formulario) ==================== */
  /* ------------------------------------------------------------------ */
  const handleSubmit = e => {
    e.preventDefault();

    if (selectedProducts.length === 0) {
      alert("Debe seleccionar al menos un producto");
      return;
    }
    if (!isCajera && !localId) {
      alert("Debe seleccionar un local");
      return;
    }
    if (Math.abs(totalPagado - total) > 0.01) {
      onOpen(); // confirmación Sweet-Alert modal
      return;
    }
    submitVenta();
  };

  const submitVenta = () => {
    onClose();

    const data = {
      fecha_venta: fechaVenta,
      local_id:    localId,
      productos:   selectedProducts.map(p => ({
        producto_id: p.id,
        cantidad:    p.cantidad,
        cortesia:    p.cortesia ? "si" : "",
      })),
      pagos:       payments,
    };

    if (esDelivery) {
      Object.assign(data, {
        es_delivery:       1,
        nombre_cliente:    cliente.nombre,
        direccion_cliente: cliente.direccion,
        numero_cliente:    cliente.numero,
        costo_delivery:    deliveryCost,
      });
    }

    setShowSuccess(true);

    setTimeout(() => {
      router.post(route("ventas.store"), data, {
        onError:  errors => console.error("❌ Errores Inertia:", errors),
        onFinish: ()     => console.log("✅ Petición terminada"),
      });
    }, 800);
  };

  /* ------------------------------------------------------------------ */
  /* ================== ICONOS PARA MÉTODO DE PAGO ==================== */
  /* ------------------------------------------------------------------ */
  const getPaymentIcon = method => {
    switch (method) {
      case "efectivo":
        return <BanknotesIcon className="h-4 w-4 text-green-600" />;
      case "yape":
      case "yape2":
      case "plin":
        return <QrCodeIcon className="h-4 w-4 text-purple-600" />;
      case "pedidosya":
        return <TruckIcon className="h-4 w-4 text-red-500" />;
      default:
        return <CreditCardIcon className="h-4 w-4 text-blue-600" />;
    }
  };

  /* ------------------------------------------------------------------ */
  /* ============================== UI ================================ */
  /* ------------------------------------------------------------------ */
  return (
    <AuthenticatedLayout>
      <Head title="Registrar Venta" />

      {/* ---------- Overlay de “registrando” ---------- */}
      {showSuccess && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white p-8 rounded-xl shadow-2xl flex flex-col items-center animate-pulse">
            <CheckCircleIcon className="h-16 w-16 text-green-500 mb-3" />
            <h2 className="text-2xl font-bold text-gray-800">Registrando venta...</h2>
          </div>
        </div>
      )}

      {/* ---------- Modal diferencia de pago ---------- */}
      <Modal isOpen={isOpen} onClose={onClose} backdrop="blur" scrollBehavior="inside">
        <ModalContent>
          <ModalHeader className="flex flex-col gap-1">
            <div className="flex items-center gap-2 text-amber-700">
              <ExclamationCircleIcon className="h-6 w-6" />
              Confirmar diferencia en pago
            </div>
          </ModalHeader>
          <ModalBody>
            <p className="text-gray-700">
              El monto total de la venta es{" "}
              <span className="font-bold">S/ {total.toFixed(2)}</span>{" "}
              pero el total pagado es{" "}
              <span className="font-bold">S/ {totalPagado.toFixed(2)}</span>.
            </p>
            {totalPagado > total ? (
              <p className="text-green-600 mt-2">
                Sobra&nbsp;S/ {(totalPagado - total).toFixed(2)} a favor del cliente.
              </p>
            ) : (
              <p className="text-red-600 mt-2">
                Faltan&nbsp;S/ {(total - totalPagado).toFixed(2)} por pagar.
              </p>
            )}
            <p className="mt-3 text-gray-600">¿Desea continuar?</p>
          </ModalBody>
          <ModalFooter>
            <Button variant="light" color="danger" onPress={onClose}>
              Cancelar
            </Button>
            <Button color="primary" onPress={submitVenta}>
              Confirmar de todos modos
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      {/* ------------------ FORM PRINCIPAL ------------------ */}
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* -------- Cabecera -------- */}
        <div className="flex justify-between items-center mb-6 bg-gradient-to-r from-blue-600 to-indigo-700 p-4 rounded-xl shadow-md text-white">
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2">
              <ShoppingCartIcon className="h-7 w-7" />
              Registrar Venta
            </h1>
            <p className="text-blue-100 flex items-center gap-1 mt-1">
              <CalendarIcon className="h-4 w-4" />
              {new Date(fechaVenta).toLocaleDateString("es-ES", {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric",
              })}
            </p>
          </div>

          <div className="flex flex-col items-end">
            <button
              type="submit"
              className="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded"
            >
              <CheckIcon className="h-5 w-5" />
              Confirmar Venta — S/ {total.toFixed(2)}
            </button>
            {isCajera && (
              <span className="text-xs mt-1 text-blue-100 flex items-center gap-1">
                <HomeIcon className="h-3 w-3" />
                {locales.find(l => l.id === parseInt(localId))?.nombre_local}
              </span>
            )}
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* ============== COLUMNA IZQUIERDA ============== */}
          <div className="lg:col-span-8 space-y-5">
            {/* --------------- Tabla de productos ---------------- */}
            <Card className="border border-blue-100 shadow-md overflow-hidden bg-white">
              <CardHeader className="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3">
                <div className="flex justify-between items-center">
                  <span className="font-semibold flex items-center gap-2">
                    <ShoppingCartIcon className="h-5 w-5" />
                    Productos Seleccionados
                  </span>
                  <div className="flex items-center gap-2">
                    <Badge color="primary" variant="flat" className="bg-white text-blue-700">
                      {selectedProducts.length} productos
                    </Badge>
                    <Badge color="success" variant="flat" className="bg-white text-green-700">
                      S/ {total.toFixed(2)}
                    </Badge>
                  </div>
                </div>
              </CardHeader>

              <CardBody>
                <div className="overflow-y-auto h-72 rounded-lg border border-gray-200 shadow-inner">
                  <table className="w-full text-sm">
                    <thead className="bg-gray-100 text-gray-700 sticky top-0 z-10">
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
                            <div className="flex flex-col items-center py-6">
                              <ShoppingCartIcon className="h-12 w-12 text-gray-300 mb-2" />
                              <p>No hay productos seleccionados.</p>
                              <p className="text-sm text-gray-400">
                                Agregue productos desde el panel derecho.
                              </p>
                            </div>
                          </td>
                        </tr>
                      ) : (
                        selectedProducts.map((p, i) => (
                          <tr
                            key={p.id}
                            className={`border-b hover:bg-gray-50 ${
                              p.cortesia ? "bg-green-50 hover:bg-green-100" : ""
                            } transition-colors`}
                          >
                            <td className="p-3">
                              <div className="flex items-center gap-2">
                                <CubeIcon className="h-5 w-5 text-blue-500" />
                                <div>
                                  <span className="font-medium block">{p.nombre}</span>
                                  <span className="text-xs text-gray-500 block">
                                    {p.categoria}
                                  </span>
                                </div>
                                {p.cortesia && (
                                  <Chip size="sm" color="success" variant="flat" className="ml-1">
                                    Cortesía
                                  </Chip>
                                )}
                              </div>
                            </td>

                            <td className="p-3">
                              <Input
                                type="number"
                                min={1}
                                size="sm"
                                value={p.cantidad}
                                className="max-w-16 mx-auto text-center"
                                onValueChange={v => updateQuantity(i, v)}
                              />
                            </td>

                            <td className="p-3 text-center">
                              <div className="flex flex-col items-center">
                                <span className="font-medium">
                                  S/ {Number(p.precio).toFixed(2)}
                                </span>
                                {esDelivery && p.precio_delivery > 0 && (
                                  <span className="text-xs text-green-600">
                                    Precio delivery&nbsp;
                                    <small>(+S/ {Number(p.precio_delivery).toFixed(2)})</small>
                                  </span>
                                )}
                              </div>
                            </td>

                            <td className="p-3 text-center font-semibold">
                              <span className={p.cortesia ? "text-green-600" : ""}>
                                S/{" "}
                                {(
                                  p.cortesia
                                    ? 0
                                    : (Number(p.precio) + (esDelivery ? Number(p.precio_delivery) : 0)) *
                                      p.cantidad
                                ).toFixed(2)}
                              </span>
                            </td>

                            <td className="p-3 text-center">
                              <div className="flex justify-center gap-1">
                                <Tooltip
                                  content={p.cortesia ? "Quitar cortesía" : "Marcar como cortesía"}
                                >
                                  <Button
                                    size="sm"
                                    isIconOnly
                                    variant={p.cortesia ? "solid" : "flat"}
                                    color={p.cortesia ? "success" : "primary"}
                                    onPress={() => toggleCortesia(i)}
                                  >
                                    <GiftIcon className="h-4 w-4" />
                                  </Button>
                                </Tooltip>
                                <Tooltip content="Eliminar producto">
                                  <Button
                                    size="sm"
                                    isIconOnly
                                    variant="light"
                                    color="danger"
                                    onPress={() => removeProduct(i)}
                                  >
                                    <TrashIcon className="h-4 w-4" />
                                  </Button>
                                </Tooltip>
                              </div>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>

                {/* ----- Resumen de totales ----- */}
                {selectedProducts.length > 0 && (
                  <>
                    <Divider className="my-3" />

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {/* Sub-totales */}
                      <div className="bg-gray-50 p-3 rounded-lg">
                        <div className="flex justify-between items-center mb-1">
                          <span className="text-gray-600">Subtotal:</span>
                          <span className="font-semibold">S/ {subtotal.toFixed(2)}</span>
                        </div>

                        {esDelivery && (
                          <div className="flex justify-between items-center mb-1">
                            <span className="text-gray-600">Costo de delivery:</span>
                            <span className="font-semibold">
                              S/ {deliveryCost.toFixed(2)}
                            </span>
                          </div>
                        )}

                        <Divider className="my-2" />

                        <div className="flex justify-between items-center font-bold">
                          <span className="text-blue-800">TOTAL:</span>
                          <span className="text-lg text-blue-800">S/ {total.toFixed(2)}</span>
                        </div>
                      </div>

                      {/* Progreso de pago */}
                      <div className="bg-blue-50 p-3 rounded-lg">
                        <div className="flex justify-between items-center mb-1">
                          <span className="text-gray-700">Total pagado:</span>
                          <span
                            className={`font-semibold ${
                              Math.abs(totalPagado - total) > 0.01
                                ? totalPagado > total
                                  ? "text-green-600"
                                  : "text-red-600"
                                : "text-green-600"
                            }`}
                          >
                            S/ {totalPagado.toFixed(2)}
                          </span>
                        </div>

                        <Progress
                          value={paymentProgress}
                          color={
                            paymentProgress >= 100
                              ? "success"
                              : paymentProgress > 80
                              ? "primary"
                              : "warning"
                          }
                          className="h-2"
                        />

                        <div className="flex justify-between items-center mt-2">
                          {Math.abs(totalPagado - total) > 0.01 ? (
                            totalPagado > total ? (
                              <span className="text-green-600 text-sm">
                                Sobra&nbsp;S/ {(totalPagado - total).toFixed(2)}
                              </span>
                            ) : (
                              <span className="text-red-600 text-sm">
                                Falta&nbsp;S/ {(total - totalPagado).toFixed(2)}
                              </span>
                            )
                          ) : (
                            <span className="text-green-600 text-sm">Pago completo</span>
                          )}

                          <Button
                            size="sm"
                            color="primary"
                            variant="flat"
                            startContent={<ArrowPathIcon className="h-4 w-4" />}
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

            {/* --------------- Métodos de pago --------------- */}
{/* --------------- Métodos de pago --------------- */}
<Card className="border border-indigo-100 shadow-md overflow-hidden bg-white">
      <CardHeader className="bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3">
        <div className="flex justify-between items-center">
          <span className="font-semibold flex items-center gap-2">
            <CreditCardIcon className="h-5 w-5" />
            Métodos de Pago
          </span>
          <Badge color="secondary" variant="flat" className="bg-white text-indigo-700">
            {payments.length} {payments.length === 1 ? "método" : "métodos"}
          </Badge>
        </div>
      </CardHeader>

      <CardBody>
        <div className="overflow-y-auto max-h-48 rounded-lg border border-gray-200 shadow-inner">
          <table className="w-full text-sm">
            <thead className="bg-gray-100 text-gray-700 sticky top-0 z-10">
              <tr>
                <th className="p-3 text-left">Método</th>
                <th className="p-3 w-40 text-center">Monto</th>
                <th className="p-3 w-24 text-center">Acción</th>
              </tr>
            </thead>
            <tbody>
              {payments.map((pay, i) => {
                // 1) Todos los métodos posibles
                const allMethods = [
                  "efectivo",
                  "yape",
                  "plin",
                  "tarjeta",
                  "pedidosya",
                  "yape2",
                ];

                // 2) Métodos ya usados en las *otras* filas
                const used = payments
                  .filter((_, j) => j !== i)
                  .map(p => p.metodo)
                  .filter(Boolean);

                // 3) Opciones disponibles para esta fila
                const available = allMethods.filter(
                  m => m === pay.metodo || !used.includes(m)
                );

                return (
                  <tr key={i} className="border-b hover:bg-gray-50 transition-colors">
                    {/* — Método — */}
                    <td className="p-3">
                      <Select
                        /* placeholder se ve cuando selectedKeys=[] */
                        placeholder="Seleccione método…"
                        size="sm"
                        fullWidth
                        startContent={getPaymentIcon(pay.metodo)}
                        selectedKeys={pay.metodo ? [pay.metodo] : []}
                        onSelectionChange={keys => {
                          const next = Array.from(keys)[0] || "";
                          updatePayment(i, "metodo", next);
                        }}
                        className="capitalize"
                      >
                        {/* Placeholder como opción deshabilitada */}
                        <SelectItem key="" textValue="Seleccione método…" isDisabled>
                          Seleccione método…
                        </SelectItem>

                        {available.map(m => {
                          const label = m.charAt(0).toUpperCase() + m.slice(1);
                          return (
                            <SelectItem
                              key={m}
                              textValue={label}
                              value={m}
                              startContent={getPaymentIcon(m)}
                              className="capitalize"
                            >
                              {label}
                            </SelectItem>
                          );
                        })}
                      </Select>
                    </td>

                    {/* — Monto — */}
                    <td className="p-3">
                      <Input
                        type="number"
                        step="0.01"
                        size="sm"
                        value={pay.monto}
                        className="max-w-32 mx-auto text-right"
                        startContent={<span className="text-gray-500">S/</span>}
                        color={pay.monto === 0 ? "warning" : "default"}
                        onValueChange={v => updatePayment(i, "monto", v)}
                      />
                    </td>

                    {/* — Acción: borrar fila — */}
                    <td className="p-3 text-center">
                      {payments.length > 1 && (
                        <Tooltip content="Eliminar método">
                          <Button
                            isIconOnly
                            size="sm"
                            variant="light"
                            color="danger"
                            onPress={() => removePayment(i)}
                          >
                            <TrashIcon className="h-4 w-4" />
                          </Button>
                        </Tooltip>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {/* — Footer: agregar fila — */}
        <div className="mt-3 flex justify-between items-center">
          <div className="text-sm text-gray-600 flex items-center gap-1">
            <InformationCircleIcon className="h-4 w-4 text-indigo-500" />
            Puede agregar múltiples métodos de pago
          </div>
          <Button
            size="sm"
            color="secondary"
            variant="flat"
            onPress={addPayment}
            startContent={<PlusCircleIcon className="h-4 w-4" />}
            className="hover:bg-indigo-100 transition-colors"
          >
            Agregar Método
          </Button>
        </div>
      </CardBody>
</Card>

          </div>

          {/* ============== COLUMNA DERECHA ============== */}
          <div className="lg:col-span-4 space-y-5">
            {/* -------- Buscador + listado de productos -------- */}
            <Card className="border border-green-100 shadow-md overflow-hidden bg-white">
              <CardHeader className="bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3">
                <div className="flex justify-between items-center">
                  <span className="font-semibold flex items-center gap-2">
                    <CubeIcon className="h-5 w-5" />
                    Catálogo de Productos
                  </span>
                </div>
              </CardHeader>

              <CardBody>
                <div className="mb-3">
                  <Input
                    placeholder="Buscar productos..."
                    fullWidth
                    variant="bordered"
                    size="sm"
                    value={searchTerm}
                    onValueChange={setSearchTerm}
                    startContent={<MagnifyingGlassIcon className="h-4 w-4" />}
                    className="mb-2"
                  />

                  {/* Chips de categorías */}
                  <div className="flex gap-1 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                    {categorias.map(cat => (
                      <Chip
                        key={cat}
                        color={categoriaSeleccionada === cat ? "primary" : "default"}
                        variant={categoriaSeleccionada === cat ? "solid" : "flat"}
                        className="cursor-pointer capitalize"
                        onClick={() => setCategoriaSeleccionada(cat)}
                      >
                        {cat}
                      </Chip>
                    ))}
                  </div>
                </div>

                {/* Tabs grid / list */}
                <Tabs
                  aria-label="Opciones de visualización"
                  variant="underlined"
                  color="success"
                  classNames={{
                    tabList: "border-b border-divider",
                    cursor: "w-full bg-green-500",
                    tab: "max-w-fit px-2 h-10",
                  }}
                >
                  {/* Vista cuadrícula */}
                  <Tab
                    key="grid"
                    title={
                      <div className="flex items-center gap-2">
                        <CubeIcon className="h-4 w-4" />
                        <span>Cuadrícula</span>
                      </div>
                    }
                  >
                    <div className="grid grid-cols-2 gap-2 mt-2 overflow-y-auto max-h-64 p-1">
                      {filteredProducts.length === 0 ? (
                        <div className="col-span-2 flex flex-col items-center justify-center p-8 text-gray-500 bg-gray-50 rounded-lg">
                          <MagnifyingGlassIcon className="h-8 w-8 mb-2 text-gray-400" />
                          <p>No se encontraron productos</p>
                        </div>
                      ) : (
                        filteredProducts.map(p => (
                          <div
                            key={p.id}
                            className="bg-white border hover:border-green-300 rounded-lg p-2 cursor-pointer shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-24"
                            onClick={() =>
                              addProduct({
                                id: p.id,
                                nombre: p.nombre,
                                precio: p.precio,
                                precio_delivery: p.precio_delivery,
                                categoria: p.categoria,
                              })
                            }
                          >
                            <div className="flex justify-between items-start">
                              <span className="truncate font-medium text-sm">{p.nombre}</span>
                              <Chip
                                size="sm"
                                variant="flat"
                                color="primary"
                                className="capitalize text-xs"
                              >
                                {p.categoria || "general"}
                              </Chip>
                            </div>
                            <div className="flex justify-between items-center">
                              <span className="text-xs text-gray-500">
                                {p.codigo || "SKU-" + p.id}
                              </span>
                              <span className="text-green-600 font-bold">
                                S/ {Number(p.precio).toFixed(2)}
                              </span>
                            </div>
                          </div>
                        ))
                      )}
                    </div>
                  </Tab>

                  {/* Vista lista */}
                  <Tab
                    key="list"
                    title={
                      <div className="flex items-center gap-2">
                        <ReceiptPercentIcon className="h-4 w-4" />
                        <span>Lista</span>
                      </div>
                    }
                  >
                    <div className="overflow-y-auto max-h-64 mt-2 border border-gray-100 rounded-lg">
                      <ul className="divide-y divide-gray-100">
                        {filteredProducts.length === 0 ? (
                          <li className="flex flex-col items-center justify-center p-8 text-gray-500 bg-gray-50">
                            <MagnifyingGlassIcon className="h-8 w-8 mb-2 text-gray-400" />
                            <p>No se encontraron productos</p>
                          </li>
                        ) : (
                          filteredProducts.map(p => (
                            <li
                              key={p.id}
                              className="p-3 hover:bg-green-50 cursor-pointer flex justify-between items-center transition-colors"
                              onClick={() =>
                                addProduct({
                                  id: p.id,
                                  nombre: p.nombre,
                                  precio: p.precio,
                                  precio_delivery: p.precio_delivery,
                                  categoria: p.categoria,
                                })
                              }
                            >
                              <div>
                                <div className="flex items-center gap-2">
                                  <CubeIcon className="h-4 w-4 text-green-500" />
                                  <span className="font-medium">{p.nombre}</span>
                                </div>
                                <span className="text-xs text-gray-500 ml-6">
                                  {p.categoria || "sin categoría"}
                                </span>
                              </div>
                              <span className="text-green-600 font-bold whitespace-nowrap">
                                S/ {Number(p.precio).toFixed(2)}
                              </span>
                            </li>
                          ))
                        )}
                      </ul>
                    </div>
                  </Tab>
                </Tabs>

                <small className="text-gray-500 block mt-3 text-center">
                  <Tooltip content="Haga clic sobre un producto para agregarlo al carrito">
                    <div className="flex items-center justify-center gap-1 bg-gray-50 px-3 py-2 rounded-md">
                      <PlusCircleIcon className="h-3 w-3" />
                      <span>Clic para agregar productos</span>
                    </div>
                  </Tooltip>
                </small>
              </CardBody>
            </Card>

            {/* -------- Información de la venta (local, fecha) -------- */}
            {!isCajera && (
              <Card className="border border-amber-100 shadow-md overflow-hidden bg-white">
                <CardHeader className="bg-gradient-to-r from-amber-500 to-orange-500 text-white py-3">
                  <span className="font-semibold flex items-center gap-2">
                    <HomeIcon className="h-5 w-5" />
                    Información de la Venta
                  </span>
                </CardHeader>
                <CardBody className="space-y-3">
                  <Select
                    label="Local"
                    fullWidth
                    variant="bordered"
                    value={localId}
                    onValueChange={setLocalId}
                    labelPlacement="outside"
                    isRequired
                    errorMessage={!localId ? "Debe seleccionar un local" : ""}
                    startContent={<HomeIcon className="h-4 w-4 text-amber-500" />}
                  >
                    <SelectItem value="">Seleccione local...</SelectItem>
                    {locales.map(l => (
                      <SelectItem key={l.id} value={String(l.id)}>
                        {l.nombre_local}
                      </SelectItem>
                    ))}
                  </Select>

                  <Input
                    label="Fecha de venta"
                    type="date"
                    fullWidth
                    variant="bordered"
                    value={fechaVenta}
                    onValueChange={setFechaVenta}
                    labelPlacement="outside"
                    startContent={<CalendarIcon className="h-4 w-4 text-amber-500" />}
                  />
                </CardBody>
              </Card>
            )}

            {/* ---------------------- Opciones de delivery ---------------------- */}
            <Card className="border border-purple-100 shadow-md overflow-hidden bg-white">
              <CardHeader className="bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white py-3">
                <span className="font-semibold flex items-center gap-2">
                  <TruckIcon className="h-5 w-5" />
                  Opciones de Delivery
                </span>
              </CardHeader>

              <CardBody className="space-y-3">
                <div className="flex items-center justify-between">
                  <Checkbox
                    checked={esDelivery}
                    onChange={e => setEsDelivery(e.target.checked)}
                    color="secondary"
                  >
                    <span className="font-medium">Habilitar Delivery</span>
                  </Checkbox>

                  {esDelivery && (
                    <Chip color="secondary" variant="flat">
                      +S/ {deliveryCost.toFixed(2)}
                    </Chip>
                  )}
                </div>

                {esDelivery && (
                  <div className="space-y-3 pt-3 border-t border-gray-100 mt-2">
                    {/* Datos cliente */}
                    <div className="flex items-center gap-2 mb-2 text-purple-700">
                      <UserIcon className="h-5 w-5" />
                      <span className="font-medium">Datos del Cliente</span>
                    </div>

                    <Input
                      label="Nombre Cliente"
                      fullWidth
                      variant="bordered"
                      value={cliente.nombre}
                      onValueChange={v => setCliente(c => ({ ...c, nombre: v }))}
                      labelPlacement="outside"
                      isRequired
                      size="sm"
                    />
                    <Input
                      label="Dirección Cliente"
                      fullWidth
                      variant="bordered"
                      value={cliente.direccion}
                      onValueChange={v => setCliente(c => ({ ...c, direccion: v }))}
                      labelPlacement="outside"
                      isRequired
                      size="sm"
                    />
                    <Input
                      label="Teléfono Cliente"
                      fullWidth
                      variant="bordered"
                      value={cliente.numero}
                      onValueChange={v => setCliente(c => ({ ...c, numero: v }))}
                      labelPlacement="outside"
                      isRequired
                      size="sm"
                    />

                    {/* Aviso */}
                    <div className="text-xs text-gray-500 bg-purple-50 p-2 rounded-md flex items-start gap-2">
                      <InformationCircleIcon className="h-4 w-4 mt-0.5 flex-shrink-0" />
                      <span>
                        El costo de delivery se calcula automáticamente con el{" "}
                        <strong>precio de delivery</strong> de cada producto.
                      </span>
                    </div>
                  </div>
                )}
              </CardBody>
            </Card>
          </div>
        </div>

        {/* ---------- Botón flotante (móvil) ---------- */}
        {selectedProducts.length > 0 && (
          <div className="fixed bottom-6 right-6 z-20 lg:hidden">
            <Button
              size="lg"
              color="success"
              isIconOnly
              onPress={handleSubmit}
              className="shadow-lg rounded-full h-14 w-14"
            >
              <CheckIcon className="h-6 w-6" />
            </Button>
          </div>
        )}
      </form>
    </AuthenticatedLayout>
  );
}

