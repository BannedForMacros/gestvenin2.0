import React, { useState, useEffect } from 'react'
import { Head, Link, router, usePage } from '@inertiajs/react'
import {
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
  Chip, Tooltip, Button, useDisclosure,
  Select, SelectItem, Input,
  Modal, ModalContent, ModalHeader, ModalBody, ModalFooter,
} from '@heroui/react'
import {
  EyeIcon, PencilIcon, PlusIcon,
  MagnifyingGlassIcon, CheckIcon,
} from '@heroicons/react/24/outline'
import axios from 'axios'
import toast from 'react-hot-toast'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import RequerimientoModal from './RequerimientoModal'

export default function RequerimientosLocalIndex() {
  const { auth, flash, requerimientos = [], locales = [], productos = [] } = usePage().props

  // Toasts de flash
  useEffect(() => {
    if (flash.success) toast.success(flash.success)
    if (flash.error)   toast.error(flash.error)
    if (flash.info)    toast(flash.info, { icon: 'ℹ️' })
  }, [flash])

  // Estados de filtro
  const [search, setSearch]       = useState('')
  const [selLocal, setSelLocal]   = useState('')
  const [selEstado, setSelEstado] = useState('')
  const [filtered, setFiltered]   = useState(requerimientos)

  // Refiltrar al cambiar filtros o la lista original
  useEffect(() => {
    let tmp = [...requerimientos]

    if (search) {
      const q = search.toLowerCase()
      tmp = tmp.filter(r =>
        r.id.toString().includes(q) ||
        (r.observaciones || '').toLowerCase().includes(q)
      )
    }
    if (selLocal)  tmp = tmp.filter(r => r.local_id.toString() === selLocal)
    if (selEstado) tmp = tmp.filter(r => r.estado === selEstado)

    setFiltered(tmp)
  }, [search, selLocal, selEstado, requerimientos])

  // Modal inline Observaciones
  const { isOpen, onOpen, onClose } = useDisclosure()
  const [selReq, setSelReq] = useState(null)
  const [obs, setObs]       = useState('')
  function abrirModalObs(req) {
    setSelReq(req)
    setObs(req.observaciones || '')
    onOpen()
  }
  async function guardarObs() {
    try {
      const { data } = await axios.put(
        `/requerimientos_local/${selReq.id}/observaciones`,
        { observaciones: obs }
      )
      if (data.success) {
        toast.success('Observaciones actualizadas')
        onClose()
        selReq.observaciones = obs
        setFiltered([...filtered])
      }
    } catch {
      toast.error('Error al actualizar observaciones')
    }
  }

  // Modal completo Ver/Editar
  const { isOpen: fullOpen, onOpen: fullOn, onClose: fullOff } = useDisclosure()
  const [fullReq, setFullReq] = useState(null)
  function openFullModal(req) {
    setFullReq(req)
    fullOn()
  }
  function handleSavedFull() {
    fullOff()
    router.reload({ only: ['requerimientos'] })
  }

  // Confirmar (solo cajera)
  async function confirmarReq(id) {
    try {
      const { data } = await axios.post(`/requerimientos_local/${id}/confirm`)
      if (data.success) {
        toast.success(data.message)
        setFiltered(filtered.map(r =>
          r.id === id ? { ...r, estado: 'no_atendido' } : r
        ))
      }
    } catch {
      toast.error('Error al confirmar')
    }
  }

  // Roles y helpers de estado
  const roles    = auth.user.roles.map(r => r.name)
  const isAdmin  = roles.includes('admin') || roles.includes('dueño')
  const isCajera = roles.includes('cajera')
  const estadoColor = {
    pendiente:   'warning',
    no_atendido: 'primary',
    atendido:    'success',
  }
  const estadoTexto = {
    pendiente:   'Pendiente',
    no_atendido: 'En Proceso',
    atendido:    'Atendido',
  }

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="text-xl font-semibold">Requerimientos de Local</h2>}
    >
      <Head title="Requerimientos de Local" />

      <div className="bg-white p-6 rounded-lg shadow">
        {/* Encabezado + Nuevo */}
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-bold">Requerimientos</h1>
          {(isAdmin || isCajera) && (
            <Link href={route('requerimientos_local.create')}>
              <Button color="primary" startContent={<PlusIcon className="w-5 h-5"/>}>
                Nuevo
              </Button>
            </Link>
          )}
        </div>

        {/* Filtros */}
        <div className="flex flex-wrap gap-4 mb-6">
          <Input
            isClearable
            placeholder="Buscar ID/obs..."
            startContent={<MagnifyingGlassIcon className="w-5 h-5 text-gray-400"/>}
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="w-full sm:w-64"
          />

          <Select
            value={selLocal}
            onValueChange={setSelLocal}
            className="w-full sm:w-48"
          >
            <SelectItem value="">Todos</SelectItem>
            {locales.map(l => (
              <SelectItem key={l.id} value={l.id.toString()}>
                {l.nombre}
              </SelectItem>
            ))}
          </Select>

          <Select
            value={selEstado}
            onValueChange={setSelEstado}
            className="w-full sm:w-48"
          >
            <SelectItem value="">Todos</SelectItem>
            <SelectItem value="pendiente">Pendiente</SelectItem>
            <SelectItem value="no_atendido">En Proceso</SelectItem>
            <SelectItem value="atendido">Atendido</SelectItem>
          </Select>
        </div>

        {/* Tabla */}
        <Table aria-label="Requerimientos">
          <TableHeader>
            <TableColumn>ID</TableColumn>
            <TableColumn>Local</TableColumn>
            <TableColumn>Fecha</TableColumn>
            <TableColumn>Estado</TableColumn>
            <TableColumn>Observaciones</TableColumn>
            <TableColumn># Prod.</TableColumn>
            <TableColumn>Acciones</TableColumn>
          </TableHeader>
          <TableBody emptyContent="No hay registros">
            {filtered.map(r => (
              <TableRow key={r.id}>
                <TableCell>#{r.id}</TableCell>
                <TableCell>{r.local?.nombre || '–'}</TableCell>
                <TableCell>
                  {new Date(r.fecha_requerimiento).toLocaleString('es-ES', {
                    day:'2-digit', month:'2-digit', year:'numeric',
                    hour:'2-digit', minute:'2-digit'
                  })}
                </TableCell>
                <TableCell>
                  <Chip variant="flat" color={estadoColor[r.estado]} size="sm">
                    {estadoTexto[r.estado]}
                  </Chip>
                </TableCell>
                <TableCell className="max-w-xs truncate">{r.observaciones}</TableCell>
                <TableCell>
                  <Chip variant="flat" size="sm">{r.detalles?.length || 0}</Chip>
                </TableCell>
                <TableCell className="flex gap-2">
                  <Tooltip content="Ver / Editar">
                    <Button isIconOnly size="sm" variant="light" onPress={()=>openFullModal(r)}>
                      <EyeIcon className="w-5 h-5"/>
                    </Button>
                  </Tooltip>
                  <Tooltip content="Editar Observaciones">
                    <Button isIconOnly size="sm" variant="light" onPress={()=>abrirModalObs(r)}>
                      <PencilIcon className="w-5 h-5"/>
                    </Button>
                  </Tooltip>
                  {isCajera && r.estado==='pendiente' && (
                    <Tooltip content="Confirmar">
                      <Button isIconOnly color="success" size="sm" variant="light" onPress={()=>confirmarReq(r.id)}>
                        <CheckIcon className="w-5 h-5"/>
                      </Button>
                    </Tooltip>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {/* Modal Observaciones */}
      <Modal isOpen={isOpen} onClose={onClose}>
        <ModalContent>
          {closeFn=>(
            <>
              <ModalHeader>Editar Observaciones</ModalHeader>
              <ModalBody>
                <Input
                  label="Observaciones"
                  value={obs}
                  onChange={e=>setObs(e.target.value)}
                />
              </ModalBody>
              <ModalFooter>
                <Button variant="flat" color="danger" onPress={closeFn}>Cancelar</Button>
                <Button color="primary" onPress={guardarObs}>Guardar</Button>
              </ModalFooter>
            </>
          )}
        </ModalContent>
      </Modal>

      {/* Modal Completo */}
      <RequerimientoModal
        isOpen={fullOpen}
        onClose={fullOff}
        requerimiento={fullReq}
        locales={locales}
        productos={productos}
        onSaved={handleSavedFull}
      />
    </AuthenticatedLayout>
  )
}
