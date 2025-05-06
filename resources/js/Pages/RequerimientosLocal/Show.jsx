// resources/js/Pages/RequerimientosLocal/Show.jsx
import React, { useState, useEffect } from 'react';
import { Head, usePage, useForm } from '@inertiajs/inertia-react';
import { Inertia } from '@inertiajs/inertia';
import {
  Card,
  CardBody,
  CardHeader,
  CardFooter,
  Button,
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Chip,
  useDisclosure,
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Input,
  Autocomplete,
  AutocompleteItem,
  Select,
  SelectItem,
  Tooltip,
  Divider
} from "@nextui-org/react";
import { EditIcon, TrashIcon, PlusIcon, CheckIcon, ArrowLeftIcon } from '../../../Components/Icons';
import MainLayout from '@/Layouts/MainLayout';
import axios from 'axios';
import toast from 'react-hot-toast';

const RequerimientosLocalShow = ({ requerimiento, productos }) => {
  const { auth, flash } = usePage().props;
  const { isOpen, onOpen, onOpenChange, onClose } = useDisclosure();
  const [selectedProducto, setSelectedProducto] = useState(null);
  const [modalMode, setModalMode] = useState('add'); // 'add' o 'edit'
  const [selectedDetalle, setSelectedDetalle] = useState(null);
  const [cantidadEnviada, setCantidadEnviada] = useState(0);
  
  const isLogistica = auth.user.roles.some(role => role.name === "logística");
  const isCajera = auth.user.roles.some(role => role.name === "cajera");
  const isAdmin = auth.user.roles.some(role => role.name === "admin" || role.name === "dueño");
  
  const { data, setData, post, errors, processing } = useForm({
    producto_id: '',
  });
  
  useEffect(() => {
    if (flash.success) {
      toast.success(flash.success);
    }
    if (flash.error) {
      toast.error(flash.error);
    }
  }, [flash]);
  
  const handleConfirmRequerimiento = async () => {
    try {
      const response = await axios.put(`/requerimientos_local/${requerimiento.id}/confirm`);
      if (response.data.success) {
        toast.success(response.data.message);
        // Recargar para reflejar el cambio de estado
        Inertia.reload();
      }
    } catch (error) {
      toast.error('Ocurrió un error al confirmar el requerimiento');
    }
  };
  
  const openAddProductoModal = () => {
    setModalMode('add');
    setSelectedProducto(null);
    onOpen();
  };
  
  const openEditCantidadModal = (detalle) => {
    setModalMode('edit');
    setSelectedDetalle(detalle);
    setCantidadEnviada(detalle.cantidad_enviada);
    onOpen();
  };
  
  const handleAgregarProducto = () => {
    if (!selectedProducto) {
      toast.error('Por favor seleccione un producto');
      return;
    }
    
    setData('producto_id', selectedProducto.id);
    
    post(route('requerimientos_local.agregar_producto', requerimiento.id), {
      onSuccess: () => {
        toast.success('Producto agregado correctamente');
        onClose();
      },
      onError: (errors) => {
        console.error(errors);
        toast.error(errors.producto_id || 'Error al agregar el producto');
      }
    });
  };
  
  const handleActualizarCantidad = () => {
    if (cantidadEnviada <= 0) {
      toast.error('La cantidad enviada debe ser mayor a 0');
      return;
    }
    
    post(route('requerimientos_local.actualizar_cantidad', { requerimiento: requerimiento.id, detalle: selectedDetalle.id }), {
      data: { cantidad_enviada: cantidadEnviada },
      onSuccess: () => {
        toast.success('Cantidad actualizada correctamente');
        onClose();
      },
      onError: (errors) => {
        console.error(errors);
        toast.error(errors.cantidad_enviada || 'Error al actualizar la cantidad');
      }
    });
  }

    const handleEliminarProducto = (detalle) => {
        if (confirm('¿Está seguro de que desea eliminar este producto del requerimiento?')) {
        Inertia.delete(route('requerimientos_local.eliminar_producto', { requerimiento: requerimiento.id, detalle: detalle.id }), {
            onSuccess: () => {
            toast.success('Producto eliminado correctamente');
            },
            onError: (errors) => {
            console.error(errors);
            toast.error(errors.producto_id || 'Error al eliminar el producto');
            }
        });
        }
    };
    
    return (
        <MainLayout auth={auth}>
            <Head title={`Requerimiento #${requerimiento.id}`} />
            <div className="container mx-auto p-4">
                <Card>
                    <CardHeader className="flex justify-between items-center">
                        <h2 className="text-xl font-bold">Requerimiento #{requerimiento.id}</h2>
                        {isLogistica && requerimiento.estado === 'pendiente' && (
                            <Button color="success" onClick={handleConfirmRequerimiento}>
                                Confirmar Requerimiento
                            </Button>
                        )}
                    </CardHeader>
                    <CardBody>
                        <Table aria-label="Detalles del requerimiento" css={{ height: "auto", minWidth: "100%" }}>
                            <TableHeader>
                                <TableColumn>Producto</TableColumn>
                                <TableColumn>Cantidad Solicitada</TableColumn>
                                <TableColumn>Cantidad Enviada</TableColumn>
                                {isLogistica && requerimiento.estado === 'pendiente' && (
                                    <TableColumn>Acciones</TableColumn>
                                )}
                            </TableHeader>
                            <TableBody>
                                {requerimiento.detalles.map((detalle) => (
                                    <TableRow key={detalle.id}>
                                        <TableCell>{detalle.producto.nombre}</TableCell>
                                        <TableCell>{detalle.cantidad_solicitada}</TableCell>
                                        <TableCell>{detalle.cantidad_enviada}</TableCell>
                                        {isLogistica && requerimiento.estado === 'pendiente' && (
                                            <TableCell className="flex space-x-2">
                                                <Tooltip content="Editar cantidad enviada">
                                                    <Button size="sm" color="warning" onClick={() => openEditCantidadModal(detalle)}><EditIcon /></Button>
                                                </Tooltip>
                                                <Tooltip content="Eliminar producto">
                                                    <Button size="sm" color="error" onClick={() => handleEliminarProducto(detalle)}><TrashIcon /></Button>
                                                </Tooltip>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {isLogistica && requerimiento.estado === 'pendiente' && (
                            <div className="mt-4 flex justify-end">
                                <Button color="primary" onClick={openAddProductoModal}>
                                    Agregar Producto
                                    <PlusIcon className="ml-2
" />
                                </Button>
                            </div>
                        )}
                    </CardBody>
                </Card>

                <Modal isOpen={isOpen} onOpenChange={onOpenChange}>
                    <ModalContent>
                        <ModalHeader>{modalMode === 'add' ? 'Agregar Producto' : 'Actualizar Cantidad Enviada'}</ModalHeader>
                        <ModalBody>
                            {modalMode === 'add' ? (
                                <Autocomplete
                                    label="Seleccionar Producto"
                                    placeholder="Buscar producto..."
                                    onSelect={(value) => {
                                        const producto = productos.find(p => p.id === parseInt(value));
                                        setSelectedProducto(producto);
                                    }}
                                >
                                    {productos.map((producto) => (
                                        <AutocompleteItem key={producto.id} value={producto.id}>
                                            {producto.nombre}
                                        </AutocompleteItem>
                                    ))}
                                </Autocomplete>
                            ) : (
                                <Input
                                    type="number"
                                    label="Cantidad Enviada"
                                    value={cantidadEnviada}
                                    onChange={(e) => setCantidadEnviada(e.target.value)}
                                />
                            )}
                        </ModalBody>
                        <ModalFooter>
                            <Button color="primary" onClick={modalMode === 'add' ? handleAgregarProducto : handleActualizarCantidad}>
                                {modalMode === 'add' ? 'Agregar' : 'Actualizar'}
                            </Button>
                            <Button color="error" onClick={onClose}>Cancelar</Button>
                        </ModalFooter>
                    </ModalContent>
                </Modal>

            </div>
        </MainLayout>
    );
}