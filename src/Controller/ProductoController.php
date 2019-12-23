<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Form\ProductoType;
use App\Repository\ProductoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


/**
 * @Route("/producto")
 */
class ProductoController extends AbstractController
{
    /**
     * @Route("/", name="producto_index", methods={"GET"})
     */
    public function index(ProductoRepository $productoRepository): Response
    {
        return $this->render('producto/index.html.twig', [
            'productos' => $productoRepository->findAll(),
        ]);
    }
    
    /**
     * @Route("/new", name="producto_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {

        $data = $request->request->get('data');
        $producto = new Producto();
        $producto->setClaveProducto($data['clave']);
        $producto->setNombre($data['nombre']);
        $producto->setPrecio($data['precio']);
        $existente = $this->getDoctrine()->getRepository(Producto::class)->findOneBy(array('clave_producto'=>$producto->getClaveProducto()));
        if($existente == NULL){
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($producto);
            $entityManager->flush();
        }
        $dataResponse = $this->serializeProducto($producto);
        $response = new Response($dataResponse);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    
    /**
     * @Route("/{id}/edit", name="producto_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Producto $producto): Response
    {
        $data = $request->request->get('data');
        $producto->setClaveProducto($data['clave']);
        $producto->setNombre($data['nombre']);
        $producto->setPrecio($data['precio']);
        $existente = $this->getDoctrine()->getRepository(Producto::class)->findOneBy(array('clave_producto'=>$data['clave']));
        
        $this->getDoctrine()->getManager()->flush();

        $dataResponse = $this->serializeProducto($producto);
        $response = new Response($dataResponse);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/{id}", name="producto_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Producto $producto): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($producto);
        $entityManager->flush();
        $response = new Response(json_encode(array('is_deleted' => true)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    private function serializeProducto(Producto $producto){
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($producto, 'json');
        return $jsonContent;
    }


    /**
     * @Route("/export", name="producto_export",methods={"GET"})
     */
    public function export(Request $request, ProductoRepository $productoRepository):Response{
        $productos = $productoRepository->findAll();
        $finalData = array();
        foreach ($productos as $key => $value) {
            $decode_data = json_decode($this->serializeProducto($value),true);
            array_push($finalData,$decode_data);
        }
        $this->export_file($finalData, "Productos", "Productos");

    }


    public function export_file($data,$titleSheet,$title,$options=null){
        $spreadsheet= new Spreadsheet();
        $firstRow='1';
        if($options!=null){
            if(key_exists("removeKeys",$options)){
                if(is_array($options['removeKeys'])){
                    foreach ($options['removeKeys'] as $key) {
                        if(key_exists($key,$data[0])){
                            foreach ($data as $k => $value) {
                                unset($value[$key]);
                                $data[$k]=$value;
                            }
                        }else{
                            throw new Exception('La llave '.$key.' no existe en el array');
                        }
                    }
                }
            }
            if(key_exists("instructions",$options)){
                $spreadsheet->getActiveSheet()->setCellValue('A'.$firstRow,$options['instructions']);
                $firstRow+=1;
            }
        }
        $keys=array_keys($data[0]);
        $spreadsheet->getActiveSheet()->fromArray($keys,NULL,'A'.$firstRow);
        $spreadsheet->getActiveSheet()->fromArray($data,NULL,'A'.($firstRow+1));
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=".$title.".xls");
        header('Cache-Control: max-age=0');
        $writer=\PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xls");
        $writer->save('php://output');
    }

}
