// resources/js/Pages/GastosVentas/Create.jsx
import React, { useState, useEffect } from 'react'
import { Head, usePage, router } from "@inertiajs/react";
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import {
  Button,
  Card,
  DatePicker,
  CardHeader,
  CardBody,
  CardFooter,
  Input,
  Select,
  SelectItem,
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Tooltip,
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  useDisclosure,
  Chip,
  Progress,
  Tabs,
  Tab
} from '@heroui/react'
import {
  PlusIcon,
  TrashIcon,
  CalendarIcon,
  ReceiptPercentIcon,
  BuildingOffice2Icon,
  ExclamationCircleIcon,
  CheckIcon,
  PencilIcon,
  DocumentTextIcon,
  ViewColumnsIcon
} from '@heroicons/react/24/outline'
import { CheckCircleIcon, InformationCircleIcon } from '@heroicons/react/24/solid'

export default function Create() {
  /** 1. PROPS Y SETUP INICIAL */
  const { locales, tiposGasto, clasificacionesGasto, auth, fechaActual } =
    usePage().props

  const isAdmin =
    auth.roles.includes('dueño') || auth.roles.includes('admin')
  const defaultLoc = auth.roles.includes('cajera')
    ? auth.user.local_id
    : ''

  /** Helper: formatea 'YYYY-MM-DD' → 'DD/MM/YYYY' */
  const formatFecha = fechaStr => {
    if (!fechaStr) return ''
    const [y, m, d] = fechaStr.split('-')
    return `${d.padStart(2, '0')}/${m.padStart(2, '0')}/${y}`
  }

  /** Pestañas y Modales */
  const [activeTab, setActiveTab] = useState('edicion')
  const { isOpen, onOpen, onClose } = useDisclosure()        // Confirmación
  const {
    isOpen: isHelpOpen,
    onOpen: onHelpOpen,
    onClose: onHelpClose
  } = useDisclosure()                                        // Ayuda

  /** 2. MAPA DE DESCRIPCIONES */
  const descripciones = {
    '1': {
      '1': [
        'Almuerzo',
        'Cena',
        'Cortesía',
        'Cumpleaños Personal',
        'Desayuno',
        'Emergencia Salud',
        'Festividad Colaboración',
        'Movilidad'
      ],
      '2': ['Es Salud', 'IGV Sunat', 'ONP', 'Préstamo Banco', 'Visa Comisión'],
      '3': ['Delivery Sueldo', 'Gerente Sueldo'],
      '4': ['Colaboración Club', 'Decoración', 'Publicidad Video'],
      '5': [
        'Boletas de Venta',
        'Carnet Salud',
        'Certificado Indeci Daniel',
        'Emergencia Salud',
        'Envió Encomienda',
        'Papeleta Multa'
      ],
      '17': ['Alimentación y Transporte'],
      '19': ['Publicidad'],
      '20': ['Trámites y Documentos'],
      '28': ['Finanzas Impuestos']
    },
    '2': {
      '6': [
        'Baldes Cremas',
        'Bancos Plásticos',
        'Cubiertos',
        'Filtro de Agua',
        'Freidora de Aire',
        'Gorros de Baldes',
        'Tabla de Picar Acero'
      ],
      '7': ['Movilidad'],
      '8': [
        'Cinta Adesiva',
        'Dispensador de Cinta',
        'Impresora Almacén',
        'Lapicero',
        'Pizarra',
        'Recibos Caja',
        'Resaltador',
        'Sello Gerencia',
        'Ventilador'
      ],
      '9': [
        'Auto Gasolina',
        'Camión Gas',
        'Camión Gasolina',
        'Minivan Gas',
        'Minivan Gasolina',
        'Moto Gasolina'
      ],
      '10': [
        'Gaseosa Descarte',
        'Pepsi Descarte',
        'Pollo Chaufa',
        'Pollo Descarte'
      ],
      '11': [
        'Caja Descanso',
        'Cena',
        'Cocina Apoyo',
        'Cocina Descanso',
        'Delivery Apoyo',
        'Delivery Sueldo',
        'Gerente Sueldo',
        'Horno Apoyo',
        'Horno Descanso',
        'Horno Sueldo',
        'Logística Apoyo',
        'Supervisor',
        'Supervisor Sueldo'
      ],
      '12': [
        'Auto Cochera',
        'Camión Cochera',
        'Internet',
        'Linea Celular',
        'Luz Almacén',
        'Luz Las Brisas',
        'Luz Recibo',
        'Recarga Teléfono',
        'Teléfono José'
      ],
      '5': ['Planilla'],
      '6': ['Insumo'],
      '16': ['Accesorios de Cocina'],
      '17': ['Artículos de Oficina'],
      '24': ['Mantenimiento'],
      '25': ['Combustible'],
      '26': ['Servicios']
    }
  }

  const getDescripcionesCompletas = (tipoId, clasificacionId) => {
    if (!tipoId || !clasificacionId) return []
    if (descripciones[tipoId]?.[clasificacionId])
      return descripciones[tipoId][clasificacionId]
    const clasif = clasificacionesGasto.find(c => c.id == clasificacionId)
    return clasif ? [clasif.nombre] : ['Otro']
  }

  /** 3. ESTADO DE GASTOS */
  const makeEmpty = () => ({
    _uid: Date.now() + Math.random(),
    fecha_gasto: fechaActual,
    tipo_gasto_id: '',
    clasificacion_gasto_id: '',
    descripcion: '',
    comprobante_de_pago: '',
    monto: '',
    local_id: isAdmin ? '' : defaultLoc,
    isEditing: true
  })

  const [gastos, setGastos] = useState([makeEmpty()])
  const [total, setTotal] = useState(0)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')
  const [successMessage, setSuccessMessage] = useState('')
  const [validationErrors, setValidationErrors] = useState({})

  useEffect(() => {
    setTotal(
      gastos.reduce((sum, g) => sum + (parseFloat(g.monto) || 0), 0)
    )
  }, [gastos])

  const byTipo = tipoId =>
    tipoId
      ? clasificacionesGasto.filter(c => c.tipo_gasto_id == tipoId)
      : []

  const getDescripciones = (tipoId, clasificacionId) =>
    getDescripcionesCompletas(tipoId, clasificacionId)

  /** Actualiza campo de la fila i */
  const updateRow = (i, field, value) => {
    const arr = [...gastos]
    arr[i][field] = value
    if (field === 'tipo_gasto_id') {
      arr[i].clasificacion_gasto_id = ''
      arr[i].descripcion = ''
    }
    if (field === 'clasificacion_gasto_id') {
      arr[i].descripcion = ''
    }
    const keyErr = `gastos.${i}.${field}`
    if (validationErrors[keyErr]) {
      const ve = { ...validationErrors }
      delete ve[keyErr]
      setValidationErrors(ve)
    }
    setGastos(arr)
  }

  /** Valida fila i */
  const validateRow = i => {
    const g = gastos[i]
    const e = {}
    if (!g.tipo_gasto_id) e[`gastos.${i}.tipo_gasto_id`] = 'Requerido'
    if (!g.clasificacion_gasto_id)
      e[`gastos.${i}.clasificacion_gasto_id`] = 'Requerido'
    if (!g.descripcion) e[`gastos.${i}.descripcion`] = 'Requerido'
    if (!g.monto || parseFloat(g.monto) <= 0)
      e[`gastos.${i}.monto`] = 'Monto inválido'
    if (isAdmin && !g.local_id) e[`gastos.${i}.local_id`] = 'Requerido'
    return e
  }

  /** Alterna edición/finalización */
  const toggleEdit = i => {
    const current = gastos[i].isEditing
    const errs = validateRow(i)
    if (current && Object.keys(errs).length) {
      setValidationErrors({ ...validationErrors, ...errs })
      return
    }
    if (current) {
      // limpia errores de esa fila
      const ve = { ...validationErrors }
      Object.keys(ve).forEach(k => {
        if (k.startsWith(`gastos.${i}.`)) delete ve[k]
      })
      setValidationErrors(ve)
    }
    const arr = [...gastos]
    arr[i].isEditing = !arr[i].isEditing
    setGastos(arr)
    // si abrimos edición, cambiamos de pestaña
    if (!current) setActiveTab('edicion')
  }

  /** Agrega nueva fila */
  const addRow = () => {
    setGastos([...gastos, makeEmpty()])
    setActiveTab('edicion')
    setSuccessMessage('Nueva fila agregada')
    setTimeout(() => setSuccessMessage(''), 2000)
  }

  /** Elimina fila idx */
  const removeRow = idx => {
    const filtered = gastos.filter((_, i) => i !== idx)
    const next = filtered.length > 0 ? filtered : [makeEmpty()]
    setGastos(next)
    // limpia errores asociados
    const ve = { ...validationErrors }
    Object.keys(ve).forEach(k => {
      if (k.startsWith(`gastos.${idx}.`)) delete ve[k]
    })
    setValidationErrors(ve)
  }

  /** Duplica fila idx */
  const duplicateRow = idx => {
    const copy = {
      ...gastos[idx],
      _uid: Date.now() + Math.random(),
      isEditing: true
    }
    setGastos([...gastos, copy])
    setActiveTab('edicion')
  }

  /** Chequea que todas las filas estén completas */
  const allValid = () =>
    gastos.every(
      g =>
        g.tipo_gasto_id &&
        g.clasificacion_gasto_id &&
        g.descripcion &&
        g.monto &&
        g.fecha_gasto &&
        (isAdmin ? g.local_id : true)
    )

  /** Valida todo el formulario */
  const validateForm = () => {
    const errs = {}
    gastos.forEach((_, i) => Object.assign(errs, validateRow(i)))
    setValidationErrors(errs)
    return !Object.keys(errs).length
  }
    /** 4. Envío al backend vía router.post de Inertia */
    const handleSubmit = () => {
      setError('')
      // 1) Asegurarnos de que no quede nada en edición
      if (gastos.some(g => g.isEditing)) {
        setError('Finaliza la edición de todas las filas.')
        return
      }
      // 2) Validar
      if (!validateForm()) {
        setError('Completa los campos requeridos.')
        return
      }
      // 3) Confirmación al usuario
      if (!window.confirm(`¿Guardar ${gastos.length} gasto(s) por S/ ${total.toFixed(2)}?`)) {
        return
      }
  
      setSubmitting(true)
      // Preparamos el payload como un objeto JS
      const payload = {
        gastos: gastos.map(({ _uid, isEditing, ...fields }) => fields)
      }
  
      router.post(route('gastos_ventas.store'), payload, {
        // conserva los campos en el formulario si hay error
        preserveState: true,
        onError: errors => {
          // aquí Laravel nos puede devolver validaciones 422
          setValidationErrors(errors)
          setError('Corrige los errores para continuar.')
        },
        onSuccess: page => {
          onOpen()
          // redirigimos tras la confirmación
          setTimeout(() => router.visit(page.props.redirect), 1200)
        },
        onFinish: () => setSubmitting(false),
      })
    }

  /** Columnas de la tabla de resumen */
  const tableColumns = [
    { name: 'N°', uid: 'index', align: 'center', width: '60px' },
    { name: 'FECHA', uid: 'fecha', align: 'start' },
    { name: 'TIPO', uid: 'tipo', align: 'start' },
    { name: 'CLASIFICACIÓN', uid: 'clasificacion', align: 'start' },
    { name: 'DESCRIPCIÓN', uid: 'descripcion', align: 'start' },
    { name: 'COMPROBANTE', uid: 'comprobante', align: 'start' },
    { name: 'MONTO', uid: 'monto', align: 'end' },
    ...(isAdmin
      ? [{ name: 'LOCAL', uid: 'local', align: 'start' }]
      : []),
    { name: 'ACCIONES', uid: 'acciones', align: 'center', width: '150px' }
  ]

  /** Items para la tabla */
  const items = gastos.map((g, i) => ({
    idx: i,
    index: i + 1,
    fecha: formatFecha(g.fecha_gasto),
    tipo: tiposGasto.find(t => t.id == g.tipo_gasto_id)?.nombre || '',
    clasificacion:
      clasificacionesGasto.find(c => c.id == g.clasificacion_gasto_id)
        ?.nombre || '',
    descripcion: g.descripcion,
    comprobante: g.comprobante_de_pago || '–',
    monto: `S/ ${(parseFloat(g.monto) || 0).toFixed(2)}`,
    local:
      locales.find(l => l.id == g.local_id)?.nombre_local || ''
  }))

  /** 5. RENDER COMPLETO */
  return (
    <AuthenticatedLayout>
      <Head title="Registro de Gastos de Venta" />

      {/* Barra superior */}
      <div className="flex justify-between items-center mb-4">
        <h1 className="text-2xl font-semibold flex items-center gap-2">
          <ReceiptPercentIcon className="h-6 w-6 text-indigo-600" />
          Registro de Gastos de Venta
        </h1>
        <div className="flex gap-2">
          <Button
            color="primary"
            variant="flat"
            startContent={<PlusIcon className="h-5 w-5" />}
            onPress={addRow}
          >
            Agregar Gasto
          </Button>
          <Button
            color="success"
            isLoading={submitting}
            onPress={handleSubmit}
            isDisabled={!allValid() || gastos.some(g => g.isEditing)}
          >
            Guardar todo
          </Button>
          <Tooltip content="Ayuda">
            <Button
              isIconOnly
              color="secondary"
              variant="light"
              onPress={onHelpOpen}
            >
              <InformationCircleIcon className="h-5 w-5" />
            </Button>
          </Tooltip>
        </div>
      </div>

      {/* Alertas */}
      {!!error && (
        <Card className="mb-4 bg-red-50">
          <CardBody className="flex items-center gap-2 text-red-700">
            <ExclamationCircleIcon className="h-5 w-5" />
            {error}
          </CardBody>
        </Card>
      )}
      {!!successMessage && (
        <Card className="mb-4 bg-green-50">
          <CardBody className="flex items-center gap-2 text-green-700">
            <CheckCircleIcon className="h-5 w-5" />
            {successMessage}
          </CardBody>
        </Card>
      )}

      {/* Resumen superior */}
      <Card className="mb-4">
        <CardBody>
          <div className="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
              <div className="flex items-center gap-2 mb-1">
                <Chip color="primary" size="sm">
                  {gastos.length}
                </Chip>
                <span>Gasto(s) registrado(s)</span>
              </div>
              <div className="font-semibold">
                Total: S/ {total.toFixed(2)}
              </div>
            </div>
            <div className="w-full md:w-1/2">
              <div className="flex justify-between text-sm">
                <span>Progreso</span>
                <span>
                  {gastos.filter(g => !g.isEditing).length} / {gastos.length}{' '}
                  completados
                </span>
              </div>
              <Progress
                color="success"
                aria-label="Progreso"
                value={
                  (gastos.filter(g => !g.isEditing).length / gastos.length) *
                  100
                }
              />
            </div>
          </div>
        </CardBody>
      </Card>

      {/* Pestañas */}
      <Tabs
        selectedKey={activeTab}
        onSelectionChange={setActiveTab}
        variant="bordered"
        color="primary"
        className="mb-4"
      >
        {/* Edición */}
        <Tab
          key="edicion"
          title={
            <div className="flex items-center gap-2">
              <PencilIcon className="h-4 w-4" />
              <span>Edición de gastos</span>
              {gastos.some(g => g.isEditing) && (
                <Chip size="sm" color="warning">
                  {gastos.filter(g => g.isEditing).length}
                </Chip>
              )}
            </div>
          }
        >
          <div className="space-y-4 mt-2">
            {gastos.filter(g => g.isEditing).length === 0 ? (
              <Card className="border border-dashed border-gray-300 bg-gray-50">
                <CardBody className="flex flex-col items-center py-8 text-center">
                  <DocumentTextIcon className="h-12 w-12 text-gray-400 mb-2" />
                  <h3 className="text-lg font-medium text-gray-700">
                    No hay gastos en modo edición
                  </h3>
                  <p className="text-gray-500 mb-4">
                    Todos los gastos están listos para guardar
                  </p>
                  <Button
                    color="primary"
                    variant="flat"
                    startContent={<PlusIcon className="h-5 w-5" />}
                    onPress={addRow}
                  >
                    Agregar nuevo gasto
                  </Button>
                </CardBody>
              </Card>
            ) : (
              gastos
                .filter(g => g.isEditing)
                .map(gasto => {
                  const idx = gastos.findIndex(g => g._uid === gasto._uid)
                  return (
                    <Card
                      key={gasto._uid}
                      className="border-blue-200 border-2"
                    >
                      <CardHeader className="bg-blue-50 py-2 flex justify-between">
                        <div className="flex items-center gap-2">
                          <Chip color="primary" size="sm">
                            {idx + 1}
                          </Chip>
                          <span className="font-semibold">
                            Gasto de Venta
                          </span>
                          <Chip color="warning" size="sm" variant="flat">
                            En edición
                          </Chip>
                        </div>
                        {gastos.length > 1 && (
                          <Tooltip content="Eliminar gasto">
                            <Button
                              isIconOnly
                              size="sm"
                              variant="light"
                              color="danger"
                              onPress={() => removeRow(idx)}
                            >
                              <TrashIcon className="h-4 w-4" />
                            </Button>
                          </Tooltip>
                        )}
                      </CardHeader>
                      <CardBody>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                          {/* Fecha */}
                          <div>
                            <label className="text-sm font-medium mb-1">
                              Fecha <span className="text-primary">*</span>
                            </label>
                            <div className="flex items-center gap-2">
                              <CalendarIcon className="h-5 w-5 text-gray-400" />
                              <DatePicker
                                selected={
                                  gasto.fecha_gasto
                                    ? new Date(gasto.fecha_gasto)
                                    : null
                                }
                                onChange={ rawValue => {
                                  const d = rawValue instanceof Date
                                    ? rawValue
                                    : new Date(rawValue)
                                  updateRow(
                                    idx,
                                    'fecha_gasto',
                                    d.toISOString().split('T')[0]
                                  )
                                }}
                                dateFormat="dd/MM/yyyy"
                                className="max-w-[200px] border rounded px-2 py-1"
                              />
                            </div>
                            {validationErrors[
                              `gastos.${idx}.fecha_gasto`
                            ] && (
                              <p className="text-red-500 text-xs mt-1">
                                {
                                  validationErrors[
                                    `gastos.${idx}.fecha_gasto`
                                  ]
                                }
                              </p>
                            )}
                          </div>

                          {/* Tipo */}
                          <div>
                            <label className="text-sm font-medium">
                              Tipo <span className="text-primary">*</span>
                            </label>
                            <Select
                              placeholder="Seleccione tipo"
                              selectedKeys={
                                gasto.tipo_gasto_id
                                  ? [gasto.tipo_gasto_id]
                                  : []
                              }
                              onChange={e =>
                                updateRow(
                                  idx,
                                  'tipo_gasto_id',
                                  e.target.value
                                )
                              }
                              isInvalid={Boolean(
                                validationErrors[
                                  `gastos.${idx}.tipo_gasto_id`
                                ]
                              )}
                            >
                              {tiposGasto.map(t => (
                                <SelectItem key={t.id} value={t.id}>
                                  {t.nombre}
                                </SelectItem>
                              ))}
                            </Select>
                            {validationErrors[
                              `gastos.${idx}.tipo_gasto_id`
                            ] && (
                              <p className="text-red-500 text-xs mt-1">
                                {
                                  validationErrors[
                                    `gastos.${idx}.tipo_gasto_id`
                                  ]
                                }
                              </p>
                            )}
                          </div>

                          {/* Clasificación */}
                          <div>
                            <label className="text-sm font-medium">
                              Clasificación <span className="text-primary">*</span>
                            </label>
                            <Select
                              placeholder={
                                gasto.tipo_gasto_id
                                  ? 'Seleccione clasificación'
                                  : 'Seleccione tipo primero'
                              }
                              selectedKeys={
                                gasto.clasificacion_gasto_id
                                  ? [gasto.clasificacion_gasto_id]
                                  : []
                              }
                              onChange={e =>
                                updateRow(
                                  idx,
                                  'clasificacion_gasto_id',
                                  e.target.value
                                )
                              }
                              isDisabled={!gasto.tipo_gasto_id}
                              isInvalid={Boolean(
                                validationErrors[
                                  `gastos.${idx}.clasificacion_gasto_id`
                                ]
                              )}
                            >
                              {byTipo(gasto.tipo_gasto_id).map(c => (
                                <SelectItem key={c.id} value={c.id}>
                                  {c.nombre}
                                </SelectItem>
                              ))}
                            </Select>
                            {validationErrors[
                              `gastos.${idx}.clasificacion_gasto_id`
                            ] && (
                              <p className="text-red-500 text-xs mt-1">
                                {
                                  validationErrors[
                                    `gastos.${idx}.clasificacion_gasto_id`
                                  ]
                                }
                              </p>
                            )}
                          </div>

                          {/* Descripción */}
                          <div className="md:col-span-3">
                            <label className="text-sm font-medium">
                              Descripción <span className="text-primary">*</span>
                            </label>
                            <Select
                              placeholder="Descripción"
                              selectedKeys={
                                gasto.descripcion ? [gasto.descripcion] : []
                              }
                              onChange={e =>
                                updateRow(idx, 'descripcion', e.target.value)
                              }
                              isDisabled={
                                !gasto.tipo_gasto_id ||
                                !gasto.clasificacion_gasto_id
                              }
                              isInvalid={Boolean(
                                validationErrors[
                                  `gastos.${idx}.descripcion`
                                ]
                              )}
                            >
                              {getDescripciones(
                                gasto.tipo_gasto_id,
                                gasto.clasificacion_gasto_id
                              ).map(d => (
                                <SelectItem key={d} value={d}>
                                  {d}
                                </SelectItem>
                              ))}
                            </Select>
                            {validationErrors[
                              `gastos.${idx}.descripcion`
                            ] && (
                              <p className="text-red-500 text-xs mt-1">
                                {
                                  validationErrors[
                                    `gastos.${idx}.descripcion`
                                  ]
                                }
                              </p>
                            )}
                          </div>

                          {/* Comprobante */}
                          <div>
                            <label className="text-sm font-medium">
                              Comprobante
                            </label>
                            <Input
                              placeholder="Serie/Número"
                              value={gasto.comprobante_de_pago}
                              onChange={e =>
                                updateRow(
                                  idx,
                                  'comprobante_de_pago',
                                  e.target.value
                                )
                              }
                            />
                          </div>

                          {/* Monto */}
                          <div>
                            <label className="text-sm font-medium">
                              Monto (S/.) <span className="text-primary">*</span>
                            </label>
                            <Input
                              type="number"
                              step="0.01"
                              placeholder="0.00"
                              value={gasto.monto}
                              onChange={e =>
                                updateRow(idx, 'monto', e.target.value)
                              }
                              isInvalid={Boolean(
                                validationErrors[`gastos.${idx}.monto`]
                              )}
                            />
                            {validationErrors[
                              `gastos.${idx}.monto`
                            ] && (
                              <p className="text-red-500 text-xs mt-1">
                                {validationErrors[`gastos.${idx}.monto`]}
                              </p>
                            )}
                          </div>

                          {/* Local */}
                          {isAdmin && (
                            <div>
                              <label className="text-sm font-medium">
                                Local <span className="text-primary">*</span>
                              </label>
                              <Select
                                placeholder="Seleccione local"
                                selectedKeys={
                                  gasto.local_id ? [gasto.local_id] : []
                                }
                                onChange={e =>
                                  updateRow(idx, 'local_id', e.target.value)
                                }
                                isInvalid={Boolean(
                                  validationErrors[`gastos.${idx}.local_id`]
                                )}
                              >
                                {locales.map(l => (
                                  <SelectItem key={l.id} value={l.id}>
                                    {l.nombre_local}
                                  </SelectItem>
                                ))}
                              </Select>
                              {validationErrors[
                                `gastos.${idx}.local_id`
                              ] && (
                                <p className="text-red-500 text-xs mt-1">
                                  {
                                    validationErrors[
                                      `gastos.${idx}.local_id`
                                    ]
                                  }
                                </p>
                              )}
                            </div>
                          )}
                        </div>
                      </CardBody>
                      <CardFooter className="flex justify-between bg-blue-50 py-2">
                        <Button
                          size="sm"
                          color="success"
                          variant="flat"
                          startContent={<CheckIcon className="h-4 w-4" />}
                          onPress={() => toggleEdit(idx)}
                        >
                          Finalizar
                        </Button>
                        <div className="flex gap-2">
                          <Tooltip content="Duplicar">
                            <Button
                              isIconOnly
                              size="sm"
                              color="primary"
                              variant="light"
                              onPress={() => duplicateRow(idx)}
                            >
                              <PlusIcon className="h-4 w-4" />
                            </Button>
                          </Tooltip>
                          <Tooltip content="Eliminar">
                            <Button
                              isIconOnly
                              size="sm"
                              color="danger"
                              variant="light"
                              onPress={() => removeRow(idx)}
                            >
                              <TrashIcon className="h-4 w-4" />
                            </Button>
                          </Tooltip>
                        </div>
                      </CardFooter>
                    </Card>
                  )
                })
            )}
          </div>
        </Tab>

        {/* Resumen */}
        <Tab
          key="resumen"
          title={
            <div className="flex items-center gap-2">
              <ViewColumnsIcon className="h-4 w-4" />
              <span>Resumen de gastos</span>
            </div>
          }
        >
          <Card className="mt-2">
            <CardBody>
              <Table
                aria-label="Resumen de gastos"
                css={{ height: 'auto', minWidth: '100%' }}
              >
                <TableHeader columns={tableColumns}>
                  {column => (
                    <TableColumn key={column.uid} {...column}>
                      {column.name}
                    </TableColumn>
                  )}
                </TableHeader>
                <TableBody items={items}>
                  {item => (
                    <TableRow key={item.idx}>
                      {columnKey => {
                        if (columnKey === 'acciones') {
                          return (
                            <TableCell key="acciones" align="center">
                              <Tooltip content="Editar">
                                <Button
                                  isIconOnly
                                  size="sm"
                                  variant="light"
                                  onPress={() => toggleEdit(item.idx)}
                                >
                                  <PencilIcon className="h-4 w-4" />
                                </Button>
                              </Tooltip>
                              <Tooltip content="Eliminar">
                                <Button
                                  isIconOnly
                                  size="sm"
                                  variant="light"
                                  color="danger"
                                  onPress={() => removeRow(item.idx)}
                                >
                                  <TrashIcon className="h-4 w-4" />
                                </Button>
                              </Tooltip>
                            </TableCell>
                          )
                        }
                        const cellValue = item[columnKey]
                        const align = tableColumns.find(
                          c => c.uid === columnKey
                        )?.align
                        return (
                          <TableCell key={columnKey} align={align}>
                            {cellValue}
                          </TableCell>
                        )
                      }}
                    </TableRow>
                  )}
                </TableBody>
              </Table>
              <div className="flex justify-end mt-4 font-semibold">
                Total: S/ {total.toFixed(2)}
              </div>
            </CardBody>
          </Card>
        </Tab>
      </Tabs>

      {/* Modal de Ayuda */}
      <Modal isOpen={isHelpOpen} onClose={onHelpClose} width="600px">
        <ModalContent className="p-4">
          <ModalHeader className="flex items-center gap-2">
            <InformationCircleIcon className="h-6 w-6 text-indigo-600" />
            Ayuda
          </ModalHeader>
          <ModalBody>
            <div className="space-y-4">
              <p className="text-sm text-gray-700">
                Aquí puedes registrar los gastos de venta de tu negocio.
                Asegúrate de completar todos los campos requeridos antes de
                guardar.
              </p>
              <ul className="list-disc pl-5 space-y-2">
                <li>Selecciona la fecha del gasto.</li>
                <li>Elige el tipo y clasificación del gasto.</li>
                <li>Proporciona una descripción clara del gasto.</li>
                <li>Adjunta el comprobante de pago si es necesario.</li>
                <li>Ingresa el monto del gasto.</li>
                {isAdmin && (
                  <li>Selecciona el local correspondiente al gasto.</li>
                )}
              </ul>
            </div>
          </ModalBody>
          <ModalFooter className="flex justify-end">
            <Button color="primary" onPress={onHelpClose}>
              Cerrar
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      {/* Modal de Confirmación */}
      <Modal isOpen={isOpen} onClose={onClose} width="400px">
        <ModalContent className="p-4">
          <ModalHeader className="flex items-center gap-2">
            <CheckCircleIcon className="h-6 w-6 text-green-600" />
            Gasto(s) guardado(s)
          </ModalHeader>
          <ModalBody>
            <p className="text-sm text-gray-700">
              Los gastos han sido guardados exitosamente.
            </p>
          </ModalBody>
          <ModalFooter className="flex justify-end">
            <Button color="primary" onPress={onClose}>
              Aceptar
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </AuthenticatedLayout>
  )
}
