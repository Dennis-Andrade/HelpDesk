--
-- PostgreSQL database dump
--

-- Dumped from database version 14.18
-- Dumped by pg_dump version 14.18

-- Started on 2025-09-28 23:19:27

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 2 (class 3079 OID 35080)
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- TOC entry 3754 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


--
-- TOC entry 888 (class 1247 OID 35088)
-- Name: ticket_estado; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.ticket_estado AS ENUM (
    'enviado',
    'completado',
    'cancelado'
);


ALTER TYPE public.ticket_estado OWNER TO postgres;

--
-- TOC entry 891 (class 1247 OID 35096)
-- Name: ticket_prioridad; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.ticket_prioridad AS ENUM (
    'baja',
    'media',
    'alta',
    'crítica'
);


ALTER TYPE public.ticket_prioridad OWNER TO postgres;

--
-- TOC entry 286 (class 1255 OID 35673)
-- Name: f_cooperativas_cards(text, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.f_cooperativas_cards(_q text, _limit integer DEFAULT 20, _offset integer DEFAULT 0) RETURNS TABLE(id integer, nombre text, ruc text, telefono text, email text, provincia text, canton text, segmento integer, nombre_segmento text, servicios_text text, activa boolean, total bigint)
    LANGUAGE sql STABLE
    AS $$
WITH base AS (
    SELECT * FROM public.v_cooperativas_cards
),
filtered AS (
    SELECT *
    FROM base
    WHERE _q IS NULL OR _q = ''
       OR unaccent(nombre) ILIKE '%' || unaccent(_q) || '%'
       OR ruc ILIKE '%' || _q || '%'
       OR unaccent(provincia) ILIKE '%' || unaccent(_q) || '%'
       OR unaccent(canton) ILIKE '%' || unaccent(_q) || '%'
),
counted AS (
    SELECT *, COUNT(*) OVER()::bigint AS total
    FROM filtered
)
SELECT *
FROM counted
ORDER BY nombre
LIMIT COALESCE(_limit, 20)
OFFSET COALESCE(_offset, 0);
$$;


ALTER FUNCTION public.f_cooperativas_cards(_q text, _limit integer, _offset integer) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 210 (class 1259 OID 35105)
-- Name: agenda; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.agenda (
    id_agenda integer NOT NULL,
    fecha timestamp without time zone NOT NULL,
    id_entidad integer,
    nombre_contacto character varying(200) NOT NULL,
    telefono character varying(20),
    email character varying(200),
    titulo character varying(200) NOT NULL,
    notas text,
    estado character varying(20) DEFAULT 'pendiente'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.agenda OWNER TO postgres;

--
-- TOC entry 211 (class 1259 OID 35112)
-- Name: agenda_contactos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.agenda_contactos (
    id_evento integer NOT NULL,
    id_cooperativa integer,
    titulo character varying(150) NOT NULL,
    fecha_evento date NOT NULL,
    contacto character varying(100),
    nota text,
    creado_por integer,
    estado character varying(20) DEFAULT 'Pendiente'::character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone DEFAULT now(),
    telefono_contacto character varying(50),
    oficial_nombre character varying(100),
    oficial_correo character varying(120),
    cargo character varying(100),
    CONSTRAINT agenda_contactos_estado_check CHECK (((estado)::text = ANY (ARRAY[('Pendiente'::character varying)::text, ('Completado'::character varying)::text, ('Cancelado'::character varying)::text])))
);


ALTER TABLE public.agenda_contactos OWNER TO postgres;

--
-- TOC entry 212 (class 1259 OID 35121)
-- Name: agenda_contactos_id_evento_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.agenda_contactos_id_evento_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.agenda_contactos_id_evento_seq OWNER TO postgres;

--
-- TOC entry 3755 (class 0 OID 0)
-- Dependencies: 212
-- Name: agenda_contactos_id_evento_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.agenda_contactos_id_evento_seq OWNED BY public.agenda_contactos.id_evento;


--
-- TOC entry 213 (class 1259 OID 35122)
-- Name: agenda_id_agenda_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.agenda_id_agenda_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.agenda_id_agenda_seq OWNER TO postgres;

--
-- TOC entry 3756 (class 0 OID 0)
-- Dependencies: 213
-- Name: agenda_id_agenda_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.agenda_id_agenda_seq OWNED BY public.agenda.id_agenda;


--
-- TOC entry 214 (class 1259 OID 35123)
-- Name: asistentes_capacitacion; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asistentes_capacitacion (
    id_asistente integer NOT NULL,
    id_capacitacion integer NOT NULL,
    id_personal integer NOT NULL,
    asistio boolean DEFAULT false,
    evaluacion integer,
    CONSTRAINT asistentes_capacitacion_evaluacion_check CHECK (((evaluacion >= 1) AND (evaluacion <= 5)))
);


ALTER TABLE public.asistentes_capacitacion OWNER TO postgres;

--
-- TOC entry 215 (class 1259 OID 35128)
-- Name: asistentes_capacitacion_id_asistente_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asistentes_capacitacion_id_asistente_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.asistentes_capacitacion_id_asistente_seq OWNER TO postgres;

--
-- TOC entry 3757 (class 0 OID 0)
-- Dependencies: 215
-- Name: asistentes_capacitacion_id_asistente_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asistentes_capacitacion_id_asistente_seq OWNED BY public.asistentes_capacitacion.id_asistente;


--
-- TOC entry 216 (class 1259 OID 35129)
-- Name: canton; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.canton (
    id integer NOT NULL,
    provincia_id integer NOT NULL,
    nombre character varying(100) NOT NULL
);


ALTER TABLE public.canton OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 35132)
-- Name: canton_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.canton_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.canton_id_seq OWNER TO postgres;

--
-- TOC entry 3758 (class 0 OID 0)
-- Dependencies: 217
-- Name: canton_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.canton_id_seq OWNED BY public.canton.id;


--
-- TOC entry 268 (class 1259 OID 35648)
-- Name: cantones; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cantones AS
 SELECT c.id AS id_canton,
    c.provincia_id,
    c.nombre
   FROM public.canton c;


ALTER TABLE public.cantones OWNER TO postgres;

--
-- TOC entry 3759 (class 0 OID 0)
-- Dependencies: 268
-- Name: VIEW cantones; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON VIEW public.cantones IS 'Vista de compatibilidad. Mapea canton.id -> id_canton';


--
-- TOC entry 218 (class 1259 OID 35133)
-- Name: capacitaciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.capacitaciones (
    id_capacitacion integer NOT NULL,
    id_contratacion integer NOT NULL,
    id_usuario_capacitador integer,
    fecha_capacitacion date NOT NULL,
    fecha_completada date,
    asistentes integer,
    estado character varying(20),
    observaciones text,
    CONSTRAINT capacitaciones_estado_check CHECK (((estado)::text = ANY (ARRAY[('Planificada'::character varying)::text, ('En progreso'::character varying)::text, ('Completada'::character varying)::text, ('Cancelada'::character varying)::text])))
);


ALTER TABLE public.capacitaciones OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 35139)
-- Name: capacitaciones_id_capacitacion_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.capacitaciones_id_capacitacion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.capacitaciones_id_capacitacion_seq OWNER TO postgres;

--
-- TOC entry 3760 (class 0 OID 0)
-- Dependencies: 219
-- Name: capacitaciones_id_capacitacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.capacitaciones_id_capacitacion_seq OWNED BY public.capacitaciones.id_capacitacion;


--
-- TOC entry 220 (class 1259 OID 35140)
-- Name: capacitaciones_providencias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.capacitaciones_providencias (
    id_capacitacion_providencia integer NOT NULL,
    id_capacitacion integer NOT NULL,
    tema_especifico character varying(200),
    normativas text,
    casos_practicos text
);


ALTER TABLE public.capacitaciones_providencias OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 35145)
-- Name: capacitaciones_providencias_id_capacitacion_providencia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.capacitaciones_providencias_id_capacitacion_providencia_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.capacitaciones_providencias_id_capacitacion_providencia_seq OWNER TO postgres;

--
-- TOC entry 3761 (class 0 OID 0)
-- Dependencies: 221
-- Name: capacitaciones_providencias_id_capacitacion_providencia_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.capacitaciones_providencias_id_capacitacion_providencia_seq OWNED BY public.capacitaciones_providencias.id_capacitacion_providencia;


--
-- TOC entry 222 (class 1259 OID 35146)
-- Name: categorias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categorias (
    id_categoria integer NOT NULL,
    nombre_categoria character varying(50) NOT NULL,
    descripcion text
);


ALTER TABLE public.categorias OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 35151)
-- Name: categorias_id_categoria_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.categorias_id_categoria_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.categorias_id_categoria_seq OWNER TO postgres;

--
-- TOC entry 3762 (class 0 OID 0)
-- Dependencies: 223
-- Name: categorias_id_categoria_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.categorias_id_categoria_seq OWNED BY public.categorias.id_categoria;


--
-- TOC entry 224 (class 1259 OID 35152)
-- Name: conm; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.conm (
    "LISTADO DE COOPERATIVAS ACTUALIZADAS DESDE JUNIO 2025;;;;;;;;;;" character varying(512)
);


ALTER TABLE public.conm OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 35157)
-- Name: contrataciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.contrataciones (
    id_contratacion integer NOT NULL,
    id_cooperativa integer NOT NULL,
    id_servicio integer NOT NULL,
    fecha_contratacion date NOT NULL,
    fecha_inicio date,
    fecha_fin date,
    estado character varying(20),
    observaciones text,
    precio_individual numeric(10,2),
    precio_grupal numeric(10,2),
    iva numeric(5,2),
    fecha_suscripcion date,
    fecha_caducidad date,
    fecha_desvinculacion date,
    estado_servicio character varying(50),
    CONSTRAINT contrataciones_estado_check CHECK (((estado)::text = ANY (ARRAY[('Pendiente'::character varying)::text, ('Activo'::character varying)::text, ('Suspendido'::character varying)::text, ('Cancelado'::character varying)::text]))),
    CONSTRAINT estado_servicio_check CHECK (((estado_servicio)::text = ANY (ARRAY[('Activo'::character varying)::text, ('Suspendido'::character varying)::text, ('Cancelado'::character varying)::text, ('Pendiente'::character varying)::text])))
);


ALTER TABLE public.contrataciones OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 35164)
-- Name: contrataciones_id_contratacion_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.contrataciones_id_contratacion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.contrataciones_id_contratacion_seq OWNER TO postgres;

--
-- TOC entry 3763 (class 0 OID 0)
-- Dependencies: 226
-- Name: contrataciones_id_contratacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.contrataciones_id_contratacion_seq OWNED BY public.contrataciones.id_contratacion;


--
-- TOC entry 227 (class 1259 OID 35165)
-- Name: contrataciones_servicios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.contrataciones_servicios (
    id_contratacion integer NOT NULL,
    id_cooperativa integer,
    id_servicio integer,
    fecha_contratacion date DEFAULT CURRENT_DATE,
    valor_contratado numeric(10,2) NOT NULL,
    periodo_facturacion character varying(20),
    activo boolean DEFAULT true,
    documento_contable character varying(255),
    fecha_finalizacion character varying(255),
    numero_licencias integer DEFAULT 1,
    fecha_ultimo_pago date,
    estado_pago character varying(20) DEFAULT 'PENDIENTE'::character varying,
    licencias_pj_matrix integer DEFAULT 0 NOT NULL,
    licencias_sic_matrix integer DEFAULT 0 NOT NULL,
    licencias_gratis_matrix integer DEFAULT 0 NOT NULL,
    licencias_pj_gratis integer DEFAULT 0 NOT NULL,
    licencias_sic_gratis integer DEFAULT 0 NOT NULL,
    licencias_sispla_matrix integer DEFAULT 0 NOT NULL,
    CONSTRAINT contrataciones_servicios_periodo_facturacion_check CHECK (((periodo_facturacion)::text = ANY (ARRAY[('Mensual'::character varying)::text, ('Trimestral'::character varying)::text, ('Semestral'::character varying)::text, ('Anual'::character varying)::text, ('Indefinido'::character varying)::text])))
);


ALTER TABLE public.contrataciones_servicios OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 35181)
-- Name: contrataciones_servicios_id_contratacion_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.contrataciones_servicios_id_contratacion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.contrataciones_servicios_id_contratacion_seq OWNER TO postgres;

--
-- TOC entry 3764 (class 0 OID 0)
-- Dependencies: 228
-- Name: contrataciones_servicios_id_contratacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.contrataciones_servicios_id_contratacion_seq OWNED BY public.contrataciones_servicios.id_contratacion;


--
-- TOC entry 229 (class 1259 OID 35182)
-- Name: cooperativa_red; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cooperativa_red (
    id_cooperativa integer NOT NULL,
    codigo_red character varying(20) NOT NULL
);


ALTER TABLE public.cooperativa_red OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 35185)
-- Name: cooperativa_servicio; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cooperativa_servicio (
    id_cooperativa integer NOT NULL,
    id_servicio integer NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    fecha_alta date DEFAULT CURRENT_DATE,
    fecha_baja date,
    notas text
);


ALTER TABLE public.cooperativa_servicio OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 35192)
-- Name: cooperativas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cooperativas (
    id_cooperativa integer NOT NULL,
    nombre character varying(100) NOT NULL,
    ruc character varying(20),
    telefono character varying(12),
    email character varying(100),
    id_segmento integer,
    fecha_registro timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    activa boolean DEFAULT true,
    tipo_entidad character varying(20) DEFAULT 'cooperativa'::character varying NOT NULL,
    servicio_activo character varying(255),
    telefono_fijo_1 character varying(12),
    telefono_fijo_2 character varying(12),
    telefono_movil character varying(12),
    email2 character varying(160),
    pais character varying(40) DEFAULT 'Ecuador'::character varying,
    notas text,
    red character varying(20),
    provincia_id integer,
    canton_id integer,
    CONSTRAINT cooperativas_email_check CHECK (((email IS NULL) OR ((email)::text ~ '^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$'::text))),
    CONSTRAINT cooperativas_red_check CHECK (((red IS NULL) OR ((red)::text = ANY (ARRAY[('UPROCACHT'::character varying)::text, ('UCACNOR'::character varying)::text, ('FECOAC'::character varying)::text])))),
    CONSTRAINT cooperativas_ruc_check_digits CHECK (((ruc IS NULL) OR ((ruc)::text ~ '^[0-9]{10,13}$'::text))),
    CONSTRAINT cooperativas_tel_check CHECK ((((telefono IS NULL) OR ((telefono)::text ~ '^[0-9+ -]{7,15}$'::text)) AND ((telefono_fijo_1 IS NULL) OR ((telefono_fijo_1)::text ~ '^[0-9+ -]{7,15}$'::text)) AND ((telefono_fijo_2 IS NULL) OR ((telefono_fijo_2)::text ~ '^[0-9+ -]{7,15}$'::text)) AND ((telefono_movil IS NULL) OR ((telefono_movil)::text ~ '^[0-9+ -]{7,15}$'::text))))
);


ALTER TABLE public.cooperativas OWNER TO postgres;

--
-- TOC entry 3765 (class 0 OID 0)
-- Dependencies: 231
-- Name: COLUMN cooperativas.red; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.cooperativas.red IS 'DEPRECATED: usar tabla cooperativa_red para múltiples redes.';


--
-- TOC entry 269 (class 1259 OID 35663)
-- Name: cooperativas_backup; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cooperativas_backup (
    id_cooperativa integer,
    nombre character varying(100),
    ruc character varying(20),
    telefono character varying(12),
    email character varying(100),
    id_segmento integer,
    fecha_registro timestamp without time zone,
    activa boolean,
    provincia character varying(100),
    canton character varying(100),
    tipo_entidad character varying(20),
    servicio_activo character varying(255),
    telefono_fijo_1 character varying(12),
    telefono_fijo_2 character varying(12),
    telefono_movil character varying(12),
    email2 character varying(160),
    pais character varying(40),
    notas text,
    red character varying(20),
    provincia_id integer,
    canton_id integer
);


ALTER TABLE public.cooperativas_backup OWNER TO postgres;

--
-- TOC entry 232 (class 1259 OID 35205)
-- Name: cooperativas_id_cooperativa_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cooperativas_id_cooperativa_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cooperativas_id_cooperativa_seq OWNER TO postgres;

--
-- TOC entry 3766 (class 0 OID 0)
-- Dependencies: 232
-- Name: cooperativas_id_cooperativa_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.cooperativas_id_cooperativa_seq OWNED BY public.cooperativas.id_cooperativa;


--
-- TOC entry 233 (class 1259 OID 35206)
-- Name: datos_facturacion; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.datos_facturacion (
    id_facturacion integer NOT NULL,
    id_cooperativa integer NOT NULL,
    direccion text,
    provincia character varying(100),
    canton character varying(100),
    email1 character varying(100),
    email2 character varying(100),
    email3 character varying(100),
    email4 character varying(100),
    email5 character varying(100),
    tel_fijo1 character varying(12),
    tel_fijo2 character varying(12),
    tel_fijo3 character varying(12),
    tel_cel1 character varying(12),
    tel_cel2 character varying(12),
    tel_cel3 character varying(12),
    contabilidad_nombre character varying(100),
    contabilidad_telefono character varying(20),
    fecha_registro timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    provincia_id integer,
    canton_id integer
);


ALTER TABLE public.datos_facturacion OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 35212)
-- Name: datos_facturacion_id_facturacion_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.datos_facturacion_id_facturacion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.datos_facturacion_id_facturacion_seq OWNER TO postgres;

--
-- TOC entry 3767 (class 0 OID 0)
-- Dependencies: 234
-- Name: datos_facturacion_id_facturacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.datos_facturacion_id_facturacion_seq OWNED BY public.datos_facturacion.id_facturacion;


--
-- TOC entry 235 (class 1259 OID 35213)
-- Name: equipos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.equipos (
    id_equipo integer NOT NULL,
    codigo_patrimonial character varying(50),
    nombre_equipo character varying(100) NOT NULL,
    tipo character varying(50),
    marca character varying(50),
    modelo character varying(50),
    id_usuario_asignado integer,
    fecha_adquisicion date,
    garantia_hasta date,
    especificaciones text,
    estado character varying(20),
    CONSTRAINT equipos_estado_check CHECK (((estado)::text = ANY (ARRAY[('Activo'::character varying)::text, ('En mantenimiento'::character varying)::text, ('Retirado'::character varying)::text, ('Dañado'::character varying)::text])))
);


ALTER TABLE public.equipos OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 35219)
-- Name: equipos_id_equipo_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.equipos_id_equipo_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.equipos_id_equipo_seq OWNER TO postgres;

--
-- TOC entry 3768 (class 0 OID 0)
-- Dependencies: 236
-- Name: equipos_id_equipo_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.equipos_id_equipo_seq OWNED BY public.equipos.id_equipo;


--
-- TOC entry 237 (class 1259 OID 35220)
-- Name: incidencias_comercial; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.incidencias_comercial (
    id_incidencia integer NOT NULL,
    id_cooperativa integer NOT NULL,
    asunto character varying(150) NOT NULL,
    descripcion text,
    prioridad character varying(20) DEFAULT 'Medio'::character varying,
    estado character varying(20) DEFAULT 'Borrador'::character varying,
    creado_por integer,
    id_ticket integer,
    created_at timestamp without time zone DEFAULT now(),
    CONSTRAINT incidencias_comercial_estado_check CHECK (((estado)::text = ANY (ARRAY[('Borrador'::character varying)::text, ('Listo'::character varying)::text, ('Enviado'::character varying)::text, ('Atendido'::character varying)::text, ('Cerrado'::character varying)::text, ('Rechazado'::character varying)::text]))),
    CONSTRAINT incidencias_comercial_prioridad_check CHECK (((prioridad)::text = ANY (ARRAY[('Crítico'::character varying)::text, ('Alto'::character varying)::text, ('Medio'::character varying)::text, ('Bajo'::character varying)::text])))
);


ALTER TABLE public.incidencias_comercial OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 35230)
-- Name: incidencias_comercial_id_incidencia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.incidencias_comercial_id_incidencia_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.incidencias_comercial_id_incidencia_seq OWNER TO postgres;

--
-- TOC entry 3769 (class 0 OID 0)
-- Dependencies: 238
-- Name: incidencias_comercial_id_incidencia_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.incidencias_comercial_id_incidencia_seq OWNED BY public.incidencias_comercial.id_incidencia;


--
-- TOC entry 239 (class 1259 OID 35231)
-- Name: incidencias_vistas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.incidencias_vistas (
    id_usuario integer NOT NULL,
    id_incidencia integer NOT NULL,
    visto_cerrada_at timestamp without time zone DEFAULT now()
);


ALTER TABLE public.incidencias_vistas OWNER TO postgres;

--
-- TOC entry 240 (class 1259 OID 35235)
-- Name: info_contabilidad; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.info_contabilidad (
    id_info integer NOT NULL,
    id_cooperativa integer,
    responsable_contable character varying(100) NOT NULL,
    email_contable character varying(100) NOT NULL,
    telefono_contable character varying(20) NOT NULL,
    ruc_contabilidad character varying(13) NOT NULL,
    direccion_contabilidad text,
    fecha_actualizacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.info_contabilidad OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 35241)
-- Name: info_contabilidad_id_info_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.info_contabilidad_id_info_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.info_contabilidad_id_info_seq OWNER TO postgres;

--
-- TOC entry 3770 (class 0 OID 0)
-- Dependencies: 241
-- Name: info_contabilidad_id_info_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.info_contabilidad_id_info_seq OWNED BY public.info_contabilidad.id_info;


--
-- TOC entry 242 (class 1259 OID 35242)
-- Name: instalaciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.instalaciones (
    id_instalacion integer NOT NULL,
    id_contratacion integer NOT NULL,
    id_usuario_tecnico integer,
    fecha_instalacion date NOT NULL,
    fecha_completada date,
    estado character varying(20),
    observaciones text,
    CONSTRAINT instalaciones_estado_check CHECK (((estado)::text = ANY (ARRAY[('Pendiente'::character varying)::text, ('En progreso'::character varying)::text, ('Completada'::character varying)::text, ('Fallida'::character varying)::text])))
);


ALTER TABLE public.instalaciones OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 35248)
-- Name: instalaciones_id_instalacion_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.instalaciones_id_instalacion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.instalaciones_id_instalacion_seq OWNER TO postgres;

--
-- TOC entry 3771 (class 0 OID 0)
-- Dependencies: 243
-- Name: instalaciones_id_instalacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.instalaciones_id_instalacion_seq OWNED BY public.instalaciones.id_instalacion;


--
-- TOC entry 244 (class 1259 OID 35249)
-- Name: listas_control; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.listas_control (
    id_lista_control integer NOT NULL,
    id_cooperativa integer NOT NULL,
    fecha_actualizacion date NOT NULL,
    responsable character varying(100),
    observaciones text
);


ALTER TABLE public.listas_control OWNER TO postgres;

--
-- TOC entry 245 (class 1259 OID 35254)
-- Name: listas_control_id_lista_control_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.listas_control_id_lista_control_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.listas_control_id_lista_control_seq OWNER TO postgres;

--
-- TOC entry 3772 (class 0 OID 0)
-- Dependencies: 245
-- Name: listas_control_id_lista_control_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.listas_control_id_lista_control_seq OWNED BY public.listas_control.id_lista_control;


--
-- TOC entry 246 (class 1259 OID 35255)
-- Name: pagos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pagos (
    id_pago integer NOT NULL,
    id_contratacion integer,
    monto numeric(10,2),
    fecha_pago date,
    metodo_pago character varying(50),
    comprobante character varying(100),
    estado character varying(20),
    observaciones text
);


ALTER TABLE public.pagos OWNER TO postgres;

--
-- TOC entry 247 (class 1259 OID 35260)
-- Name: pagos_id_pago_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pagos_id_pago_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pagos_id_pago_seq OWNER TO postgres;

--
-- TOC entry 3773 (class 0 OID 0)
-- Dependencies: 247
-- Name: pagos_id_pago_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pagos_id_pago_seq OWNED BY public.pagos.id_pago;


--
-- TOC entry 248 (class 1259 OID 35261)
-- Name: personal_cooperativa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.personal_cooperativa (
    id_personal integer NOT NULL,
    id_cooperativa integer NOT NULL,
    nombre character varying(100) NOT NULL,
    cargo character varying(100),
    telefono character varying(20),
    email character varying(100),
    departamento character varying(100)
);


ALTER TABLE public.personal_cooperativa OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 35264)
-- Name: personal_cooperativa_id_personal_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.personal_cooperativa_id_personal_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.personal_cooperativa_id_personal_seq OWNER TO postgres;

--
-- TOC entry 3774 (class 0 OID 0)
-- Dependencies: 249
-- Name: personal_cooperativa_id_personal_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.personal_cooperativa_id_personal_seq OWNED BY public.personal_cooperativa.id_personal;


--
-- TOC entry 250 (class 1259 OID 35265)
-- Name: provincia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.provincia (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL
);


ALTER TABLE public.provincia OWNER TO postgres;

--
-- TOC entry 251 (class 1259 OID 35268)
-- Name: provincia_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.provincia_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.provincia_id_seq OWNER TO postgres;

--
-- TOC entry 3775 (class 0 OID 0)
-- Dependencies: 251
-- Name: provincia_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.provincia_id_seq OWNED BY public.provincia.id;


--
-- TOC entry 267 (class 1259 OID 35644)
-- Name: provincias; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.provincias AS
 SELECT p.id AS id_provincia,
    p.nombre
   FROM public.provincia p;


ALTER TABLE public.provincias OWNER TO postgres;

--
-- TOC entry 3776 (class 0 OID 0)
-- Dependencies: 267
-- Name: VIEW provincias; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON VIEW public.provincias IS 'Vista de compatibilidad. Mapea provincia.id -> id_provincia';


--
-- TOC entry 252 (class 1259 OID 35269)
-- Name: red; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.red (
    codigo character varying(20) NOT NULL,
    nombre character varying(100) NOT NULL
);


ALTER TABLE public.red OWNER TO postgres;

--
-- TOC entry 253 (class 1259 OID 35272)
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id_rol integer NOT NULL,
    nombre_rol character varying(50) NOT NULL,
    descripcion text
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- TOC entry 254 (class 1259 OID 35277)
-- Name: roles_id_rol_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_rol_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.roles_id_rol_seq OWNER TO postgres;

--
-- TOC entry 3777 (class 0 OID 0)
-- Dependencies: 254
-- Name: roles_id_rol_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_rol_seq OWNED BY public.roles.id_rol;


--
-- TOC entry 255 (class 1259 OID 35278)
-- Name: segmentos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.segmentos (
    id_segmento integer NOT NULL,
    nombre_segmento character varying(100) NOT NULL,
    descripcion text
);


ALTER TABLE public.segmentos OWNER TO postgres;

--
-- TOC entry 256 (class 1259 OID 35283)
-- Name: segmentos_id_segmento_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.segmentos_id_segmento_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.segmentos_id_segmento_seq OWNER TO postgres;

--
-- TOC entry 3778 (class 0 OID 0)
-- Dependencies: 256
-- Name: segmentos_id_segmento_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.segmentos_id_segmento_seq OWNED BY public.segmentos.id_segmento;


--
-- TOC entry 257 (class 1259 OID 35284)
-- Name: servicios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servicios (
    id_servicio integer NOT NULL,
    nombre_servicio character varying(100) NOT NULL,
    descripcion text,
    activo boolean DEFAULT true
);


ALTER TABLE public.servicios OWNER TO postgres;

--
-- TOC entry 258 (class 1259 OID 35290)
-- Name: servicios_id_servicio_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servicios_id_servicio_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.servicios_id_servicio_seq OWNER TO postgres;

--
-- TOC entry 3779 (class 0 OID 0)
-- Dependencies: 258
-- Name: servicios_id_servicio_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servicios_id_servicio_seq OWNED BY public.servicios.id_servicio;


--
-- TOC entry 259 (class 1259 OID 35291)
-- Name: ticket_historial; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ticket_historial (
    id_historial integer NOT NULL,
    id_ticket integer,
    fecha_cambio timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    campo_modificado character varying(50),
    valor_anterior text,
    valor_nuevo text,
    id_usuario integer
);


ALTER TABLE public.ticket_historial OWNER TO postgres;

--
-- TOC entry 260 (class 1259 OID 35297)
-- Name: ticket_historial_id_historial_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ticket_historial_id_historial_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ticket_historial_id_historial_seq OWNER TO postgres;

--
-- TOC entry 3780 (class 0 OID 0)
-- Dependencies: 260
-- Name: ticket_historial_id_historial_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ticket_historial_id_historial_seq OWNED BY public.ticket_historial.id_historial;


--
-- TOC entry 261 (class 1259 OID 35298)
-- Name: tickets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tickets (
    id_ticket integer NOT NULL,
    titulo character varying(100) NOT NULL,
    descripcion text,
    id_usuario_reporta integer,
    id_equipo integer,
    id_categoria integer,
    prioridad character varying(20),
    estado character varying(20),
    fecha_apertura timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre timestamp without time zone,
    solucion text,
    id_tecnico_asignado integer,
    CONSTRAINT tickets_estado_check CHECK (((estado)::text = ANY (ARRAY[('Abierto'::character varying)::text, ('En progreso'::character varying)::text, ('Pendiente'::character varying)::text, ('Cerrado'::character varying)::text, ('Rechazado'::character varying)::text]))),
    CONSTRAINT tickets_prioridad_check CHECK (((prioridad)::text = ANY (ARRAY[('Crítico'::character varying)::text, ('Alto'::character varying)::text, ('Medio'::character varying)::text, ('Bajo'::character varying)::text])))
);


ALTER TABLE public.tickets OWNER TO postgres;

--
-- TOC entry 262 (class 1259 OID 35306)
-- Name: tickets_id_ticket_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tickets_id_ticket_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.tickets_id_ticket_seq OWNER TO postgres;

--
-- TOC entry 3781 (class 0 OID 0)
-- Dependencies: 262
-- Name: tickets_id_ticket_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tickets_id_ticket_seq OWNED BY public.tickets.id_ticket;


--
-- TOC entry 263 (class 1259 OID 35307)
-- Name: usuario_categorias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuario_categorias (
    id_usuario integer NOT NULL,
    id_categoria integer NOT NULL
);


ALTER TABLE public.usuario_categorias OWNER TO postgres;

--
-- TOC entry 264 (class 1259 OID 35310)
-- Name: usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuarios (
    id_usuario integer NOT NULL,
    username character varying(50) NOT NULL,
    password_md5 character varying(32) NOT NULL,
    id_rol integer NOT NULL,
    nombre_completo character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    activo boolean DEFAULT true,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    departamento character varying(20)
);


ALTER TABLE public.usuarios OWNER TO postgres;

--
-- TOC entry 265 (class 1259 OID 35315)
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.usuarios_id_usuario_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.usuarios_id_usuario_seq OWNER TO postgres;

--
-- TOC entry 3782 (class 0 OID 0)
-- Dependencies: 265
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.usuarios_id_usuario_seq OWNED BY public.usuarios.id_usuario;


--
-- TOC entry 270 (class 1259 OID 35668)
-- Name: v_cooperativas_cards; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.v_cooperativas_cards AS
 SELECT c.id_cooperativa AS id,
    c.nombre,
    c.ruc,
    COALESCE(NULLIF(btrim((c.telefono)::text), ''::text), NULLIF(btrim((c.telefono_movil)::text), ''::text), NULLIF(btrim((c.telefono_fijo_1)::text), ''::text), NULLIF(btrim((c.telefono_fijo_2)::text), ''::text)) AS telefono,
    NULLIF(btrim((c.email)::text), ''::text) AS email,
    p.nombre AS provincia,
    t.nombre AS canton,
    c.id_segmento AS segmento,
    s.nombre_segmento,
    COALESCE(c.servicio_activo, ''::character varying) AS servicios_text,
    c.activa
   FROM (((public.cooperativas c
     LEFT JOIN public.provincia p ON ((c.provincia_id = p.id)))
     LEFT JOIN public.canton t ON ((c.canton_id = t.id)))
     LEFT JOIN public.segmentos s ON ((c.id_segmento = s.id_segmento)));


ALTER TABLE public.v_cooperativas_cards OWNER TO postgres;

--
-- TOC entry 266 (class 1259 OID 35316)
-- Name: vw_tickets; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vw_tickets AS
 SELECT t.id_ticket,
    t.titulo,
    t.descripcion,
    t.id_usuario_reporta,
    t.id_equipo,
    t.id_categoria,
    t.prioridad,
    t.estado,
    t.fecha_apertura,
    t.fecha_cierre,
    t.solucion,
    t.id_tecnico_asignado,
    ((('SIS-'::text || to_char(t.fecha_apertura, 'YYMMDD'::text)) || '-'::text) || lpad((t.id_ticket)::text, 6, '0'::text)) AS codigo
   FROM public.tickets t;


ALTER TABLE public.vw_tickets OWNER TO postgres;

--
-- TOC entry 3344 (class 2604 OID 35320)
-- Name: agenda id_agenda; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agenda ALTER COLUMN id_agenda SET DEFAULT nextval('public.agenda_id_agenda_seq'::regclass);


--
-- TOC entry 3348 (class 2604 OID 35321)
-- Name: agenda_contactos id_evento; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agenda_contactos ALTER COLUMN id_evento SET DEFAULT nextval('public.agenda_contactos_id_evento_seq'::regclass);


--
-- TOC entry 3351 (class 2604 OID 35322)
-- Name: asistentes_capacitacion id_asistente; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistentes_capacitacion ALTER COLUMN id_asistente SET DEFAULT nextval('public.asistentes_capacitacion_id_asistente_seq'::regclass);


--
-- TOC entry 3353 (class 2604 OID 35323)
-- Name: canton id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.canton ALTER COLUMN id SET DEFAULT nextval('public.canton_id_seq'::regclass);


--
-- TOC entry 3354 (class 2604 OID 35324)
-- Name: capacitaciones id_capacitacion; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capacitaciones ALTER COLUMN id_capacitacion SET DEFAULT nextval('public.capacitaciones_id_capacitacion_seq'::regclass);


--
-- TOC entry 3356 (class 2604 OID 35325)
-- Name: capacitaciones_providencias id_capacitacion_providencia; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capacitaciones_providencias ALTER COLUMN id_capacitacion_providencia SET DEFAULT nextval('public.capacitaciones_providencias_id_capacitacion_providencia_seq'::regclass);


--
-- TOC entry 3357 (class 2604 OID 35326)
-- Name: categorias id_categoria; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorias ALTER COLUMN id_categoria SET DEFAULT nextval('public.categorias_id_categoria_seq'::regclass);


--
-- TOC entry 3358 (class 2604 OID 35327)
-- Name: contrataciones id_contratacion; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contrataciones ALTER COLUMN id_contratacion SET DEFAULT nextval('public.contrataciones_id_contratacion_seq'::regclass);


--
-- TOC entry 3371 (class 2604 OID 35328)
-- Name: contrataciones_servicios id_contratacion; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contrataciones_servicios ALTER COLUMN id_contratacion SET DEFAULT nextval('public.contrataciones_servicios_id_contratacion_seq'::regclass);


--
-- TOC entry 3379 (class 2604 OID 35329)
-- Name: cooperativas id_cooperativa; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativas ALTER COLUMN id_cooperativa SET DEFAULT nextval('public.cooperativas_id_cooperativa_seq'::regclass);


--
-- TOC entry 3385 (class 2604 OID 35330)
-- Name: datos_facturacion id_facturacion; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.datos_facturacion ALTER COLUMN id_facturacion SET DEFAULT nextval('public.datos_facturacion_id_facturacion_seq'::regclass);


--
-- TOC entry 3386 (class 2604 OID 35331)
-- Name: equipos id_equipo; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.equipos ALTER COLUMN id_equipo SET DEFAULT nextval('public.equipos_id_equipo_seq'::regclass);


--
-- TOC entry 3391 (class 2604 OID 35332)
-- Name: incidencias_comercial id_incidencia; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_comercial ALTER COLUMN id_incidencia SET DEFAULT nextval('public.incidencias_comercial_id_incidencia_seq'::regclass);


--
-- TOC entry 3396 (class 2604 OID 35333)
-- Name: info_contabilidad id_info; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.info_contabilidad ALTER COLUMN id_info SET DEFAULT nextval('public.info_contabilidad_id_info_seq'::regclass);


--
-- TOC entry 3397 (class 2604 OID 35334)
-- Name: instalaciones id_instalacion; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.instalaciones ALTER COLUMN id_instalacion SET DEFAULT nextval('public.instalaciones_id_instalacion_seq'::regclass);


--
-- TOC entry 3399 (class 2604 OID 35335)
-- Name: listas_control id_lista_control; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listas_control ALTER COLUMN id_lista_control SET DEFAULT nextval('public.listas_control_id_lista_control_seq'::regclass);


--
-- TOC entry 3400 (class 2604 OID 35336)
-- Name: pagos id_pago; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos ALTER COLUMN id_pago SET DEFAULT nextval('public.pagos_id_pago_seq'::regclass);


--
-- TOC entry 3401 (class 2604 OID 35337)
-- Name: personal_cooperativa id_personal; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_cooperativa ALTER COLUMN id_personal SET DEFAULT nextval('public.personal_cooperativa_id_personal_seq'::regclass);


--
-- TOC entry 3402 (class 2604 OID 35338)
-- Name: provincia id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.provincia ALTER COLUMN id SET DEFAULT nextval('public.provincia_id_seq'::regclass);


--
-- TOC entry 3403 (class 2604 OID 35339)
-- Name: roles id_rol; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id_rol SET DEFAULT nextval('public.roles_id_rol_seq'::regclass);


--
-- TOC entry 3404 (class 2604 OID 35340)
-- Name: segmentos id_segmento; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.segmentos ALTER COLUMN id_segmento SET DEFAULT nextval('public.segmentos_id_segmento_seq'::regclass);


--
-- TOC entry 3406 (class 2604 OID 35341)
-- Name: servicios id_servicio; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servicios ALTER COLUMN id_servicio SET DEFAULT nextval('public.servicios_id_servicio_seq'::regclass);


--
-- TOC entry 3408 (class 2604 OID 35342)
-- Name: ticket_historial id_historial; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_historial ALTER COLUMN id_historial SET DEFAULT nextval('public.ticket_historial_id_historial_seq'::regclass);


--
-- TOC entry 3410 (class 2604 OID 35343)
-- Name: tickets id_ticket; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets ALTER COLUMN id_ticket SET DEFAULT nextval('public.tickets_id_ticket_seq'::regclass);


--
-- TOC entry 3415 (class 2604 OID 35344)
-- Name: usuarios id_usuario; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id_usuario SET DEFAULT nextval('public.usuarios_id_usuario_seq'::regclass);


--
-- TOC entry 3692 (class 0 OID 35105)
-- Dependencies: 210
-- Data for Name: agenda; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.agenda (id_agenda, fecha, id_entidad, nombre_contacto, telefono, email, titulo, notas, estado, created_at) FROM stdin;
\.


--
-- TOC entry 3693 (class 0 OID 35112)
-- Dependencies: 211
-- Data for Name: agenda_contactos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.agenda_contactos (id_evento, id_cooperativa, titulo, fecha_evento, contacto, nota, creado_por, estado, created_at, updated_at, telefono_contacto, oficial_nombre, oficial_correo, cargo) FROM stdin;
1	58	sdf	2025-09-05	\N	vccvvccv	9	Pendiente	2025-09-03 16:06:35.50579	2025-09-03 16:06:35.50579	dsadasdsa	dassds	dfdf@fsaf	fsd
\.


--
-- TOC entry 3696 (class 0 OID 35123)
-- Dependencies: 214
-- Data for Name: asistentes_capacitacion; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asistentes_capacitacion (id_asistente, id_capacitacion, id_personal, asistio, evaluacion) FROM stdin;
\.


--
-- TOC entry 3698 (class 0 OID 35129)
-- Dependencies: 216
-- Data for Name: canton; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.canton (id, provincia_id, nombre) FROM stdin;
1	1	Cuenca
2	1	Girón
3	1	Gualaceo
4	1	Nabón
5	1	Paute
6	1	Pucará
7	1	San Fernando
8	1	Santa Isabel
9	1	Sígsig
10	1	Oña
11	1	Chordeleg
12	1	El Pan
13	1	Sevilla de Oro
14	1	Guachapala
15	1	Camilo Ponce Enríquez
16	2	Guaranda
17	2	Chimbo
18	2	San Miguel
19	2	Echeandía
20	2	Caluma
21	2	Las Naves
22	2	Chillanes
23	3	Azogues
24	3	Biblián
25	3	Cañar
26	3	La Troncal
27	3	El Tambo
28	3	Suscal
29	3	Déleg
30	4	Tulcán
31	4	Bolívar
32	4	Espejo
33	4	Mira
34	4	Montúfar
35	4	San Pedro de Huaca
36	5	Latacunga
37	5	La Maná
38	5	Pangua
39	5	Pujilí
40	5	Salcedo
41	5	Saquisilí
42	5	Sigchos
43	6	Riobamba
44	6	Alausí
45	6	Colta
46	6	Chambo
47	6	Chunchi
48	6	Guamote
49	6	Guano
50	6	Pallatanga
51	6	Penipe
52	6	Cumandá
53	7	Machala
54	7	Arenillas
55	7	Atahualpa
56	7	Balsas
57	7	Chilla
58	7	El Guabo
59	7	Huaquillas
60	7	Las Lajas
61	7	Marcabelí
62	7	Pasaje
63	7	Piñas
64	7	Portovelo
65	7	Santa Rosa
66	7	Zaruma
67	8	Esmeraldas
68	8	Eloy Alfaro
69	8	Muisne
70	8	Quinindé
71	8	Rioverde
72	8	San Lorenzo
73	8	Atacames
74	9	San Cristóbal
75	9	Santa Cruz
76	9	Isabela
77	10	Guayaquil
78	10	Alfredo Baquerizo Moreno (Jujan)
79	10	Balao
80	10	Balzar
81	10	Colimes
82	10	Daule
83	10	Durán
84	10	El Empalme
85	10	El Triunfo
86	10	General Antonio Elizalde (Bucay)
87	10	Isidro Ayora
88	10	Lomas de Sargentillo
89	10	Marcelino Maridueña
90	10	Milagro
91	10	Naranjal
92	10	Naranjito
93	10	Nobol (Narcisa de Jesús)
94	10	Palestina
95	10	Pedro Carbo
96	10	Playas (General Villamil)
97	10	Salitre (Urbina Jado)
98	10	Samborondón
99	10	Santa Lucía
100	10	Simón Bolívar
101	10	Yaguachi
102	11	Ibarra
103	11	Antonio Ante
104	11	Cotacachi
105	11	Otavalo
106	11	Pimampiro
107	11	San Miguel de Urcuquí
108	12	Loja
109	12	Calvas
110	12	Catamayo
111	12	Celica
112	12	Chaguarpamba
113	12	Espíndola
114	12	Gonzanamá
115	12	Macará
116	12	Olmedo
117	12	Paltas
118	12	Pindal
119	12	Puyango
120	12	Quilanga
121	12	Saraguro
122	12	Sozoranga
123	12	Zapotillo
124	13	Babahoyo
125	13	Baba
126	13	Buena Fe
127	13	Montalvo
128	13	Mocache
129	13	Palenque
130	13	Puebloviejo
131	13	Quevedo
132	13	Quinsaloma
133	13	Urdaneta
134	13	Valencia
135	13	Ventanas
136	13	Vinces
137	14	Portoviejo
138	14	Bolívar
139	14	Chone
140	14	El Carmen
141	14	Flavio Alfaro
142	14	Jama
143	14	Jaramijó
144	14	Jipijapa
145	14	Junín
146	14	Manta
147	14	Montecristi
148	14	Olmedo
149	14	Paján
150	14	Pedernales
151	14	Pichincha
152	14	Puerto López
153	14	Rocafuerte
154	14	San Vicente
155	14	Santa Ana
156	14	Sucre
157	14	Tosagua
158	14	24 de Mayo
159	15	Morona
160	15	Gualaquiza
161	15	Limón Indanza
162	15	Logroño
163	15	Pablo Sexto
164	15	Palora
165	15	San Juan Bosco
166	15	Santiago de Méndez
167	15	Sucúa
168	15	Tiwintza
169	15	Huamboya
170	15	Taisha
171	16	Tena
172	16	Archidona
173	16	Carlos Julio Arosemena Tola
174	16	El Chaco
175	16	Quijos
176	17	Aguarico
177	17	La Joya de los Sachas
178	17	Loreto
179	17	Orellana
180	18	Pastaza
181	18	Arajuno
182	18	Mera
183	18	Santa Clara
184	19	Quito
185	19	Cayambe
186	19	Mejía
187	19	Pedro Moncayo
188	19	Pedro Vicente Maldonado
189	19	Puerto Quito
190	19	Rumiñahui
191	19	San Miguel de los Bancos
192	20	Santa Elena
193	20	La Libertad
194	20	Salinas
195	21	Santo Domingo
196	21	La Concordia
197	22	Lago Agrio
198	22	Cuyabeno
199	22	Gonzalo Pizarro
200	22	Putumayo
201	22	Shushufindi
202	22	Sucumbíos
203	22	Cascales
204	23	Ambato
205	23	Baños de Agua Santa
206	23	Cevallos
207	23	Mocha
208	23	Patate
209	23	Pelileo
210	23	Santiago de Píllaro
211	23	Quero
212	23	Tisaleo
213	24	Zamora
214	24	Chinchipe
215	24	Yacuambi
216	24	Yantzaza
217	24	El Pangui
218	24	Paquisha
219	24	Nangaritza
220	24	Centinela del Cóndor
221	24	Palanda
\.


--
-- TOC entry 3700 (class 0 OID 35133)
-- Dependencies: 218
-- Data for Name: capacitaciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.capacitaciones (id_capacitacion, id_contratacion, id_usuario_capacitador, fecha_capacitacion, fecha_completada, asistentes, estado, observaciones) FROM stdin;
\.


--
-- TOC entry 3702 (class 0 OID 35140)
-- Dependencies: 220
-- Data for Name: capacitaciones_providencias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.capacitaciones_providencias (id_capacitacion_providencia, id_capacitacion, tema_especifico, normativas, casos_practicos) FROM stdin;
\.


--
-- TOC entry 3704 (class 0 OID 35146)
-- Dependencies: 222
-- Data for Name: categorias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.categorias (id_categoria, nombre_categoria, descripcion) FROM stdin;
1	Seguridad	Virus, malware, problemas de acceso no autorizado
2	Servidores	Incidentes con servidores internos o en la nube
3	Base de Datos	Problemas con SQL Server, MySQL, PostgreSQL, etc.
4	Telefonía IP	Problemas con sistemas de voz sobre IP
5	Sistemas Especializados	Problemas con software ERP, CRM o sistemas verticales
6	Acceso Remoto	Problemas con VPN, escritorio remoto o TeamViewer
7	Dispositivos Móviles	Problemas con tablets, smartphones o sus aplicaciones
8	Soporte a Usuarios	Asesoría en el uso de sistemas y capacitación
9	Instalaciones	Instalacion Matrix, PJ, SIC o SISPLA
\.


--
-- TOC entry 3706 (class 0 OID 35152)
-- Dependencies: 224
-- Data for Name: conm; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.conm ("LISTADO DE COOPERATIVAS ACTUALIZADAS DESDE JUNIO 2025;;;;;;;;;;") FROM stdin;
;;;;;;;;;;;;;;;;;;;;;;;;;;
NOMBRE DE LA COOPERATIVA;SERVICIOS QUE RECIBEN;;;N. OFC.;NO LLAMAR;SEGMENTO;TELEFONO COOPERATIVA;CIUDAD;CELULAR;CORREO INSTITUCIONAL;OFICIAL DE CUMPLIMIEMTO;NOVEDADES;CARGO;REQUERIMIENTOS;SEGUIMIENTO;NUEVO CONTROL JULIO 25;REQUERIMIENTO;RESUELTO?;CONTROL DESDE 21 DE AGOSTO 2025 ;RESUELTO?;;;;;;
COOPERATIVA DE AHORRO Y CREDITO SEÑOR DEL ARBOL;MATRIX;OK;CH;1;;4;987654321/98799257;Latacunga;991939235;jose.iza@coacsenordelarbol.com;JOSE IZA;OK;OC;NECESITA SABER QUE TECNOLOGIA USAMOS PARA EL CALCULO  DE LA MATRIZ DE RIESGO
SIGUE;;;CH;;;;;;984994921;cumplimiento@coacsenordelarbol.com;NATALY CHASILOA;;OCS;;;;;;;;;;;;;
COOPERATIVA DE AHORRO Y CREDITO VONNELAN;MATRIX;;;;;4;22851527;Rumiñahui;998836606;;JESSICA MARTINEZ;X;OC;SOLICITARON CANCELACION;;;;;;;;;;;;
COAC. EDUCADORES DE BOLIVAR ;MATRIX;OK;CH;2;;4;32550525/6;Guaranda;988784524;coopebolivar@yahoo.com;EMILIA DEL CARMEN ABRIL PARRA;OK;OC;JUSTO AHORA ESTAN TRABAJADO CON DIEGO;;;;;TODO OK. ACTUALIZAR LA INFO DE JULIO. DAR CITA. PASADO DATO A DIEGO;DIEGO LLAMÓ Y LA ING. QUEDO EN ELLA LLAMAR CUANDO SUBA INSUMOS;;;;;;
COOPERATIVA DE AHORRO Y CREDITO COORAMBATO LTDA.;PLA;OK;CH;3;;3;32826057;Ambato;+593 98 620 1200;ofi_cumplimiento@coorambato.fin.ec;JUAN YUCAILLA;;OC;;;;;;;;;;;;;
SIGUE;;;CH;;;;;;985459110;ofi_cumplimiento@coorambato.fin.ec;JENNY CAPUZ;OK;OCS;ENVIE WATHSAPP 13/06;YA RESPONDIO Y SE ACTUALIZO DATOS. NECESITA AYUDA PARA BAJAR UNOS LISTADOS AUTOMATICOS 16/06;;;;AYUDA CON LISTAS DE PROVEEDORES DUPLICADOS NOMBRES Y APARECEN MAS .Y DE PLA CON MATRIZ DE RIESGO;NO CONTESTA;;;;;;
COOPERATIVA DE AHORRO Y CREDITO ANDINA ;MATRIX;OK;CH;4;;2;329943107/32802100;Latacunga;962521811;gvalencia@coopandina.fin.ec;EDGAR GABRIEL VALENCIA RODRIGUEZ;OK;OC;;;;;;;;;;;;;
SIGUE;;;;;;;;;995928667;afadul@coopandina.fin.ec;ADRIAN OSWALDO FADUL MARCA;;OCS;;;;;;;;;;;;;
COAC DE LA MICROEMPRESA LA FORTUNA;MATRIX;invi;CH;5;;3;72 572 954;Loja;995250617;oficialcumplimiento@cofortunafin.ec;PEGGUI BAILON;OK;OC;TRASPASO PARA LAS CREDENCIALES DE ELLA A AGENCIA SARAGURO / GLEN;GLEN ENVIO REQUERIMIENTO PARA EL CAMBIO Y NO HAN RESPONDIDO. HOY 18/06 YO ENVIE RECORDATORIO WATHSAPP;;;;TODO OK. SOLO REQUIERE COMPARTIR USUARIO LISTAS DE CONTROL. KAREN PARDOENCARGADA DE PJ COMPARTIRA CON LEONARDO LUDEÑA. PASE EL DATO A SISTEMAS PARA QUE LE AYUDEN ;;;;;;;
COAC CHUNCHI ;MATRIX;OK;CH;6;;3;32936497/32936610;Chunchi;998571736;coop.chunchi.ltda@gmail.com / oficialcumplimiento@coacchunchi.fin.ec;YURIKA ZUÑA;OK;OC;;;;;;;;;;;;;
COAC GUAMOTE ;MATRIX;invi;CH;7;;4;32916258;Guamote;987788595;mmanuelay@hotmail.com;MANUELA YASACA;OK;OC;;;;;;TODO NORMAL. Y LE INVITE A LA REUNION. DICE QUE CUANDO HACEN EN RIOBAMBA PARA LOS DE UPROCACH;;;;;;;
COAC SOL DE LOS ANDES ;MATRIX;OK;CH;8;;3;32948545;Riobamba;989482879;emiranda@coacsoldelosandes.fin.ec;ESTHER CAROLINA MIRANDA;OK;OC;;;;;;VA A LLAMAR POR TEMA DE MATRICES DE RIESGO AHORA NO ESTA EN LA OFICINA;;;;;;;
COOPERATIVA DE AHORRO Y CREDITO NUEVO AMANECER LTDA. COTOPAXI;MATRIX;invi;CH;9;;5;32597709/998911061;Salcedo;988459290;edwin_m@coacnuevoamannecer.com;EDWIN MAÑAY;OK;OC;;;;;;;;;;;;;
SIGUE;;;CH;;;;984153073;;984153073;yajairauafe@coacnuevoamanecer.com;YAJAIRA TOAPANTA;;OCS;;;;;;;;;;;;;
COOPERATIVA DE AHORRO Y CREDITO RIOBAMBA LTDA.;MATRIX;OK;CH;10;;1;032 962 – 431;Riobamba;984330014;cumplimiento@cooprio.fin.ec;CRISTIAN CUADRADO;OK;;NO PASAN LOS INSUMOS TODAVIA TOTALES SI NECESITAN ALGO
SIGUE;;;;;;;;;984319564;luis.yuqui@cooprio.fin.ec;LUIS FERNANDO YUQUI ORDOÑEZ;;OCS;;;;;;;;;;;;;
UCACNOR;;;;;;;;;;;;;;;;;;;;;;;;;;
;MATRIX;invi;CH;11;;;63700380;Ibarra;995458855;;LUIS VASCONEZ;NC;OC;NUNCA CONTESTA. DIEGO LE ESCRIBIO Y DIJO TODO OK;;;;;;;;;;;;
SIGUE;MATRIX;;CH;;;;;;997552693;;DIEGO ARENAS;;OCS;ES EL SUPLETENTE PIDE HABLAR CON EL OC;;;;;;;;;;;;
COAC. SAN ANTONIO IMBABURA;PJ / MATRIX;invi;CH;12;;2;65005304;Ibarra;981179891;golivo@sanantonio.fin.ec;GABRIELA LISSETHE OLIVO ANDRADE;OK;OC;;;;;;SOLO UTILIZAN PROVIDENCIAS A PESAR DE TENER MATRIX Y ES FACTURACION DIRECTA DE VIP-G POR PJ;;;;;;;
SIGUE;;;CH;;;;;;982214478;mbenalcazar@coopsanantonio.com;MARCELA ALEXANDRA BENALCAZAR ANDRADE;;OCS;;;;;;;;;;;;;
COAC. SANTA ANITA LTDA;LC-DIG/ MATRIX;OK;CH;13;;3;62916031/62554193;Ibarra;983369363;hugo_venegas@coacsantaanita.fin.ec;HUGO TARCISIO VENEGAS MONCAYO;OK;OC;;;;;;NO HAY BASES DE SENTENCIADOS Y PEPS. NO ESTAN USANDO PLA.  SI VA A VENIR A LA REUNION Y FACRURA STEFEY 30  O ALGO ASI;COBRAMOS 150 PARA SEGMENTO. POR EL TIEMPO QUE ESTAN CON NOSOTROS 70 MAS IVA Y HACER UN ADENDUM;;;;;;
SIGUE;;;CH;;;;;;995473238;diana_robles@coacsantaanita.fin.ec;DIANA ELIZABETH ROBLES FLORES;;OCS;;;;;;;;;;;;;
COAC. SAN GABRIEL;MATRIX;;;;NUNCA CONTESTA;;;;978759057;;ALEXANDRA NAVARRETE;NC2;;;;;;;;;;;;;;
COAC. ARTESANOS;MATRIX;OK;CH;14;;2;62602940;Ibarra;967349081;oficial_cumplimiento@coopartesanos.fin.ec;CYNTHIA LOPEZ;X;OC;HACE MUCHO TIEMPO HAN PEDIDO SOPORTE POR QUE NO SE GENERAL LOS PERFILES FINANCIEROS DE LAS PERSONAS JURIDICAS;YA HABLARON GENERACION DE PERFILES PERSONAS JUDICAS/ NO GERENA ALERTAS PLA;;;;;;;;;;;
RED DE ESTRUCTURAS FINANCIERAS POPULARES Y SOLIDARIAS EQUINOCCIO REDFINPSEQ  (2130);;;;;;;;;;;;;;;;;;;;;;;;;;
COAC. 16 DE JULIO LTDA.;PLA-PJ;OK;CH;15;;2;2784376;Ascazubi;999261241;eduardo.cumplimiento@16dejulio.fin.ec;JOSÉ EDUARDO FLORES PEÑAFIEL;OK;OC;HOY JUSTAMENTE ESPERAN CONECCION CON SOPORTE A 3:30PM;;;;;;;;;;;;
SIGUE;;;;;;;;;;;;;OCS;;;;;;;;;;;;;
COAC. MANANTIAL DE ORO;PJ;OK;CH;16;;2;25002222;Machachi;984073434;sleime@manantialdeoro.fin.ec;SONIA LEIME;OK;OC;LLAMAR A PARTIR DEL 20 DE JUNIO QUE TIENE COMITÉ
SIGUE;;;CH;;;;;;978676161;faheredia@manantialdeoro.fin.ec;FRANK ANDRES HEREDIA JARAMILLO;;OCS;;;;;;;;;;;;;
COAC.  SAN CRISTOBAL;PJ-LC-PLA;OK;CH;17;;3;22230347;Quito;995478571;rproano@cristobal.fin.ec;ROBERTO PROAÑO;OK;OC;;;;;;;;;;;;;
COAC.  ESPERANZA Y PROGRESO DEL VALLE;PJ /OFICIAL ES QUIÑONEZ;invi;NA;X;NO LLAMAR;;2334368/995206387;Quito;987026528;;PATRICIA GUALOTUÑA;;OC;;;;;;;;;;;;;
COAC.  17 DE MARZO LTDA.;PJ-LC;OK;NA;X;ACTUALIZADO 21 FEBRERO;3;22832510;Quito;998226333;of.cumplimiento@17demarzo.fin.ec;LORENA HINOSTROZA;NC;OC;BORRE PLA.. DIEGO INDICA SOLO TIENEN PJ;;;;;;;;;;;;
COAC.  COOPARTAMOS;LC-PJ-PLA;invi;NA;18;;4;995326631;Sangolquí;995367690;oficialdecumplimiento@coopartamos.fin.ec;SANDRA VILLACIS;OK;OC;QUEDARON HOY EN INSTALAR EL SISTEMA A LA 3PM;;;;;;;;;;;;
COAC. EL MOLINO LTDA.;PJ-PLA;OK;CH;19;;3;0988516847/ 0994191089/22022047;Quito;987928519;kllugsha@cooperativaelmolino.fin.ec;KATHERINE LLUGSHA;OK;OC;YA TIENE EL EQUIPO PARA INSTALAR PLA Y PROCESOS JUDICIALES. SE CAMBIO FECHA INSTALACION PJ  EL LUNES A 2PM;;;;;;;;;;;;
SIGUE;;;CH;;;;;;0994265608;jguaman@cooperativaelmolino.fin.ec;JESSICA KARINA GUAMAN MORALES;;OCS;;;;;;;;;;;;;
COAC SAN JUAN DE COTOGCHOA;;;;;;;;;;;;;;;;;;;;;;;;;;
CASA DE VALORES EQUITY;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC.  IMBABURA LTDA FINANZCOOP;LC-PJ-PLA;OK;CH;20;;4;062 922 846;Otavalo;+593 96 081 4698;cumplimientoimbabura@gmail.com;DIEGO ANRANGO;OK;OC;SE HABLO YA CON VIP-G  DE LA INSTALACION DE LISTAS DE CONTROL;;;;;;;;;;;;
COAC. CÁMARA DE COMERCIO EL CARMEN LTDA;MATRIX;invi;CH;21;ACTUALIZADO 19 MARZO;3;52661705/52661652/986469680;El Carmen;983225408;bella.alvarado@cccelcarmen.fin.ec;BELLA ALVARADO;OK;OC;REVISO DIEGO CHATS ANTERIORES Y TODO ESTA EN ORDEN 13/06;;;;;;;;;;;;
COAC. SAN ANTONIO LTDA LOS RIOS;PLA-LC;invi;CH;23;;2;52714004;Montalvo;989628519;adriana.jimenez@coopsanantonio.fin.ec;ADRIANA JIMENEZ;OK;OC;;;;;;;;;;;;;
COAC.  SALITRE;MATRIX;OK;CH;24;;3;42792311 ;Salitre;985576796;cumplimiento@coacsalitre.fin.ec;GEOMAYRA SANTANA;OK;OC;YA HABLO CON DIEGO SOBRE QUE LOS DEPOSITOS SE SUMAN CON LOS RETIROS Y ESO INCREMENTA EL RIESGO. TAMBIEN DICE QUE EL SISTEMA NO ESTA DANDO SUFICIENTES ALERTAS QUE FACILITAN EL TRABAJO;;;;;;;;;;;;
SIGUE;;;CH;;;;;;+593 99 140 1192;;JULIO CESAR MALAGON;;OCS;;;;;;;;;;;;;
COAC.  GRUPO DIFARE;MATRIX;OK;CH;25;;3;4 3731390 ;Guayaquil;991865989;claudia.castillo@grupodifare.com;CLAUDIA CASTILLO;OK;OC;;;;;;;;;;;;;
SIGUE;;;CH;;;;;;+593 99 225 4700;elena.lainez@grupodifare.com;ELENA LAINEZ;;OCS;;;;;;;;;;;;;
COAC.  CAMARA DE COMERCIO LA JOYA DE LOS SACHAS;MATRIX;invi;CH;26;;3;988166219;Joya de los Sachas;985801129;rmendoza@cccjs.fin.ec;RONNY MENDOZA;OK;OC;11 JUNIO HAN PROGRAMADO CITA TEMA PROVIDENCIAS A 2:30;;;;;;;;;;;;
SIGUE;;;CH;;;;;;988166219;ychavez@cccjs.fin.ec;YADIRA CHAVEZ;;OCS;;;;;;;;;;;;;
COAC.  COCA LTDA;MATRIX;OK;CH;27;;3;63700260 ext 1001;El Coca;988417300;oficial.cumplimiento@cocaltda.fin.ec;VIVIANA OLAYA;OK;OC;;;;;;;;;;;;;
SIGUE;;;CH;;;;;;(593 6) 3700-260 ext. 1018;talento.humano@cocaltda.fin.ec;VERONICA JIMENEZ;;OCS;;;;;;;;;;;;;
COAC.  HERMES GAIBOR VERDESOTO;MATRIX;ok;CH;28;;3;32680450;Moraspungo;993397245;dorisyepez@coachermesgaibor.fin.ec;DORIS YEPEZ;OK;OC;TIENEN PERSONAL NUEVO
SIGUE;;;CH;;;;;;980744210;pabloborja@coachermesgaiborv.fin.ec;PABLO JOSE BORJA TASIGCHANA;;OCS;;;;;;;;;;;;;
COAC.  FUTURO ESFUERZO Y DISCIPLINA;PJ-PLA;invi;CH;30;;4;23342498;Quito;995288765;mf.burgos@hotmail.com;FERNANDA BURGOS;LLAMO DIEGO;OC;LUNES A 16/06 A 12PM INSTALACION DE  PLA;;;;;;;;;;;;
COAC.  FUTURO LAMANENSE;PJ-LC-Dig;n/a;;31;NO LLAMAR;3; 32568510;La Mana;996168937;oficialcumplimiento@futurolamanense.fin.ec;ROSA VALENCIA;NC;OC;;;;;;;;;;;;;
COAC. EMPLEADOS Y JUBILADOS BANCO CENTRAL DEL ECUADOR ;MATRIX;invi;CH;32;ACTUALIZADO 1 ABRIL;3; 22279145/963088334;Quito;998060805;oficial.cumplimiento@cacebce.com;MARIA TOAPANTA;NC;OC;YA HABLO DIEGO VAN A HACER UNA ACTUALIZACION DEL SISTEMA HOY 13/06;;;;;;;;;;;;
COAC. HUAICANA;MATRIX;invi;CH;33;;2;22884225/22885322;Quito;995810092;iespinosa@huaicana.fin.ec;IVAN ESPINOSA;OK;OC;;;;;;;;;;;;;
SIGUE;;;CH;;;;;;979272137;ccaizaluisa@huaicana.fin.ec;CRISTIAN CAIZALUISA;;OCS;;;;;;;;;;;;;
COAC. ORDEN Y SEGURIDAD;MATRIX;ok;CH;34;;3;255-0798;Quito;961141919;cumplimiento@ordenyseguridad.fin.ec;OMAYRA MINDA;X;OC;PROV JUDIC. SE DESABILITO Y NO HAN HECHO ACTUALIZACION. NO HICIERON DE MANERA ORDENADA.. YA HABLO CON GLEN Y LE VOLVIERON A PONER EL ANTERIOR. NO LO HICIERON EN ORDEN ES LA QUEJA. NO AVISARON CON TIEMPO PARA COMPRAR EQUIPOS ETC. ETC. TAMBIEN SE QUEJA DE QUE LE PIDAN DATOS TODO EL TIEMPO CORREOS ETC;;;;;;;;;;;;
COAC. MARCABELI;MATRIX;ok;CH;35;;3; 72956171 – Ext. 100;Marcabelí;962379135;csgaona@coacmarcabeli.fin.ec;SILVIA CARMEN GAONA;OK;OC;;;;;;;;;;;;;
SIGUE;;;;;;;;;986487634;esapolo@coacmarcabeli.fin.ec;ELANNY SAMANTHA;;;;;;;;;;;;;;;
COAC. CORPORACION CENTRO;MATRIX;ok;CH;36;ACTUALIZADO 4 ABRIL;; 22520 644/2502123/2558135/2566 004/2541 477;Quito;998749412;william.lopez@coopcentro.fin.ec;WILIAM LOPEZ;NC;OC;DIEGO INDICO NO LLAMAR 13/06;;;;;;;;;;;;
SIGUE;;;CH;;;;;;983803430;william.chiluisa@coopcentro.fin.ec;WILIAM CHILUISA;;OCS;;;;;;;;;;;;;
COAC. UNIVERSIDAD CATOLICA DEL ECUADOR ;MATRIX;OK;CH;37;;3;22991664/987378811;Quito;995559321;damorochop@coopuce.fin.ec;DIEGO MOROCHO;OK;OC;;;;;;;;;;;;;
COAC. ALLI TARPURK LTDA;PLA-PJ;OK;CH;38;ACTUALIZADO 2 ABRIL;4;22286525;Quito;987811257;marys41992@hotmail.com;MARIA PEREZ;NC;OC;DIEGO ENVIO WATHSAPP VEAMOS SI RESPONDE;;;;;;;;;;;;
SIGUE;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC. ACCION IMBABURAPAK;MATRIX;OK;CH;39;;2; 062922846 ext 1010;Otavalo;994178630;landrade@accionimbaburapak.com.ec;LUIS ANDRADE;X;OC;REQUIRIMIENTOS PARA HACER NUEVAMENTE LAS ESTRUCTURAS DEL PLA;;;;;;;;;;;;
COAC. MAGISTERIO MANABITA;MATRIX;OK;CH;40;;3;52639597/52360184;Portoviejo;982997783;cristina.garcia@coopmagisteriomanabita.fin.ec;CRISTINA GARCIA;OK;OC;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
ASOCIACION MUTUALISTA AMBATO;PLA-PJ;ok;CH;41;;1;32994790 ext: 63/41/48;Ambato;988560366;elizabeth.yungan@mutualistaambato.fin.ec;ELIZABETH YUNGAN;X;OC;REVISAR COINCIDENCIAS LC /PREVENSION…SALEN ALERTAS COMO RIESGO BAJO A PESAR DE QUE 
COAC.  EMPRENDEDORES COOPEMPRENDER LTDA;MATRIX;ok;CH;42;;3;22387332/22120508;El Quinche;958721788;cumplimiento@coopemprender.fin.ec;RUTH ANDINO;X;OC;PERSONA SENTENCIADA Y NO PUEDEN VER LA INFO. RENATO ESTABA AL TANTO;;;;;;;;;;;;
COAC. SAN VALENTIN;MATRIX;OK;CH;43;;4;022844353 / 022844813 / 022844092;Quito;998774493;fer.g2@hotmail.com;FERNANDA GAIBOR;OK;OC;HABLO DIEGO HICIERON  CONFIG DEL SISTEMA 13/06;;;;;;;;;;;;
COAC. PABLO MUÑOZ VEGA;MATRIX;ok;CH;44;;1;1800678678/997659033/64700678;Tulcán;999576122;veronicanarvaez@cpmv.fin.ec;VERONICA GARDENIA NARVAEZ;X;OC;TIENEN UN PROBLEMA CON SISTEMAS DE LA COOP. NO ES PROBLEMA DE NUESTRO SISTEMA. YA HABLARON CON PABLO PARA VISITARLOS Y RESOLVER. TAMBIEN PARA RENOVAR CONTRATO;;;;;;;;;;;;
SIGUE;;;CH;;;;;;+593 99 666 9914;lenintates@cpmv.fin.ec;LENIN TATES;;OCS;;;;;;;;;;;;;
COAC. OCCIDENTAL;PJ-LC-PLA;ok;CH;45;NO LLAMAR;3;964052222;Pujilí;983865560;amora@coacoccidental.fin.ec;AMPARITO MORA;NC1;OC;;;;;;;;;;;;;
SIGUE;;;CH;46;;;;;096 0478526;diana.lucero@coacoccidental.fin.ec;DIANA LUCERO;;OCS;;;;;;;;;;;;;
COAC. UNION  POPULAR;MATRIX;invi;;;;4;32825660/32827484;Ambato;993331866;vicky.analuisa89@gmail.com;VIRGINIA ANALUIZA;OK;OC;TODO OK
EQUITY CASA DE VALORES;LC-PJ;;;X;NO LLAMAR;NO APLICA;958845479;Quito;995552829;;GABRIELA GUAYASAMIN;;NO APLICA;;;;;;;;;;;;;
COAC. INDIGENAS GALAPAGOS LTDA;PJ-LC-PLA;ok;CH;47;NO LLAMAR;4;32485116;;967504380;criztiandavid@hotmailcom;CRISTHIAN GUAMAN;NC;OC;;;X;En el tema de PLA si nos faltaba que nos expliquen el tema de la reporteria únicamente de los datos que se generan existía casos que nos indicaban NULL la información esa era la única duda 
SIGUE;;;CH;;;;;;+593 98 229 0868;maritzalm03@outlook.com;MARITZA MASAQUIZA;;OCS;;;;;;;;;;;;;
COAC. INTEGRACION SOLIDARIA;MATRIX;OK;CH;48;;4;995451471;Salcedo; 980460279;rtixilema@integracion.fin.ec;ROBERTH TIXILEMA ;OK;OC;;;;;;;;;;;;;
COAC. 27 DE NOVIEMBRE;PLA-LC;OK;CH;49;;4;32965801;Riobamba;986916684;saruk.maila@coac27noviembre.com;SARUK MAILA;X;OC;NO ESTAN USANDO PLA POR QUE ESTAN ACTUALIZANDO INFO ALLA
UCACNOR;MATRIX;;;;;NO APLICA;;;N/A;;;NO APLICA;;;;;;;;;;;;;;
RED DE ESTRUCTURAS FINANCIERAS POPULARES Y SOLIDARIAS EQUINOCCIO REDFINPSEQ  (2130);MATRIX;;;;;;;;;;;;;;;;;;;;;;;;;
UPROCACH;MATRIX;;;;;;;;;;;;;;;;;;;;;;;;;
COAC.  COOPROGRESO ;PJ;n/a;;X;NO LLAMAR;1;958678700;Quito/Pomasqui;N/A;;;;;;;;;;;;;;;;;
COAC. ALIANZA DEL VALLE LTDA;PJ;n/a;;X;NO LLAMAR;1;22998600/990328625;Quito/Los Chillos;N/A;;;;;;;;;;;;;;;;;
COAC. 15 DE ABRIL ;PJ-PLA-LC;OK;CH;50;NO LLAMAR;1;52633032;Portoviejo;997613924;;VIVIANA ARTEAGA;NC2;OC;;;;;;;;;;;;;
SIGUE;;;CH;;;;;;992018986;;GENESIS DELGADO;;OCS;;;;;;;;;;;;;
COAC. SIERRA CENTRO ;LC-PJ;n/a;NA;X;;3;32802582/958929610;Latacunga;995258152;ochoa-paulina@sierracentro.fin.ec;PAULINA OCHOA;OK;OC;ESTAN CON MIGRACION DE DATOS COR FINANCIERO POR PARTE DE ELLOS;;;;;;;;;;;;
HATUN MUSKUY ENTIDAD FINANCIERA ;LC;n/a;NA;X;NO LLAMAR;;;;N/A;;;;;;;;;;;;;;;;;
COOPERATIVA DE AHORRO Y CERDITO ICHUBAMBA;PLA-PJ;OK;CH;51;;;32965921;Guamote;990102009;cesenhr@gmail.com;HENRY CESEN;;OC;Diego habló el 15/07/25 y están atrasados en subir la info desde febrero;;;;;;;;;;;;
COAC LA DOLOROSA;MATRIX;ok;CH;52;;;;;+593 99 455 4625;;MARÍA GABRIELA MOSQUERA;;OC;;;;;;;;;;;;;
SIGUE;;;CH;;;;;;+593 99 447 0202;;RENE CHANABA;;OCS;;;;;;;;;;;;;
ECUAFUTURO;MATRIX;invi;CH;53;;3;;;+593 99 517 4542;nathaly_morales@ecuafuturo.fin.ec;NATHALY CAROLINA MORALES;;OC;;;DIEGO LLAMO Y REVISO EL PLA. ESTAN TRABAJANDO EN CONFIG. DE SISTEMA E INSUMOS PENDIENTES 25/07/25;;REUNION CON SISTEMAS 28/07 A 2PM;;;;;;;;
;;;;;;;;;;marina_freire@ecuafuturo.fin.ec;MARINA FREIRE;;OCS;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
PROVIDENCIAS JUDICIALES;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC MARCABELÍ LTDA;;;;;;3;;;;;GALARZA ESPINOZA JOHANA DEL ROCIO;;ASIST.GEREN.;;;;;;;;;;;;;
COAC HERMES GAIBOR;;;;;;3;;;;;DORIS MARIBEL YEPEZ PAÑO;;OC;;;;;;;;;;;;;
COAC UNIVERSIDAD CATOLICA;;;;;;3;;;;;DIEGO ALBERTO MOROCHO;;OC;;;;;;;;;;;;;
COAC INTEGRACIÓN SOLIDARIA;;;;;;4;;;;;JUAN CARLOS CHANGO TELENCHANA;;ABOGADO;;;;;;;;;;;;;
COAC ANDINA LTDA;;;;;;2;;;;;EDGAR GABRIEL VALENCIA RODRIGUEZ;;OC;;;;;;;;;;;;;
COAC CHUNCHI LTDA;;;;;;3;;;;;YURIKA ASUCENA ZUÑA CALLE;;OC;;;;;;;;;;;;;
COAC ICHUBAMBA LTDA;;;;;;5;;;;;HENRY RUBEN CESEN VIMOS;;OC;;;;;;;;;;;;;
COAC SOL DE LOS ANDES LTDA;;;;;;3;;;;;ESTHER CAROLINA MIRANDA MOROCHO;;PROV. JUD;;;;;;;;;;;;;
COAC SAN ANTONIO LTDA DE LOS RIOS;;;;;;2;;;;;GABRIELA LUCILA MEDRANO SUAREZ;;ASIST.GEREN.;;;;;;;;;;;;;
COAC INDIGENA GALAPAGOS;;;;;;4;;;;;CHRISTIAN DAVID GUAMAN MASAQUIZA;;OC;;;;;;;;;;;;;
COAC SALITRE LTDA;;;;;;3;;;;;GEOMAYRA ELIZABETH SANTANA CASTRO;;OC;;;;;;;;;;;;;
COAC FORTUNA LTDA;;;;;;3;;;985960961;;KAREN JOSE PARDO TENE;;SECRET. GEREN.;;;;;;;;;;;;;
COAC HUAICANA;;;;;;2;;;;;ANDY ADRIAN LINCANGO CONDOR;;ASIST DE RIESG;;;;;;;;;;;;;
COAC EMPRENDER LTDA;;;;;;3;;;;;JENNY ALEXANDRA SANCHEZ MELENDRES;;PROV. JUD;;;;;;;;;;;;;
ASOCIACION MUTUALISTA DE AHORRO Y CRÉDITO PARA LA VIVIENDA AMBATO;;;;;;1;;;;;PAREDES JARRÍN IVONNE VERÓNICA;;ATTE CLIENTE;;;;;;;;;;;;;
COAC SAN CRISTOBAL;;;;;;3;;;;;CRISTINA MARYSOL CORMEJO ARIAS;;OCS;;;;;;;;;;;;;
COAC COOPARTAMOS;;;;;;4;;;;;SANDRA ELIZABETH VILLACIS PROAÑO;;PROV. JUD;;;;;;;;;;;;;
COAC MANANTIAL DE ORO;;;;;;2;;;;;JAIME GABRIEL TOAPANTA QUISAGUANO;;PROV. JUD;;;;;;;;;;;;;
COAC CAMARA DE COMERCIO LA JOYA DE LOS SACHAS;;;;;;3;;;;;RONNY ANTHONY MENDOZA ALONZO;;OCS;;;;;;;;;;;;;
COAC CÁMARA DE COMERCIO INDÍGENA DE GUAMOTE LTDA;;;;;;5;;;;;LUIS HERNAN MORALES LEMA;;GERENTE G.;;;;;;;;;;;;;
COAC SENOR DEL ARBOL;;;;;;4;;;;;NATALY ANGELICA CHASILOA CHIMBO;;OCS;;;;;;;;;;;;;
COAC SAN JOSE LTDA;;;;;;;;;;;MARIA ALEXANDRA SALAZAR GUARACA;;OCS;;;;;;;;;;;;;
COAC MAGISTERIO MANABITA;;;;;;3;;;;;ANGELA MARIA BARRAGAN OZAETA;;OC;;;;;;;;;;;;;
COAC ESPERANZA Y PROGRESO DEL VALLE;;;;;;4;;;984176500;edisonquinionez@esperanzayprogreso.fin.ec;EDISON QUIÑONEZ;;PROV. JUD;;;;;;;;;;;;;
COAC CAMARA DE COMERCIO EL CARMEN LTDA;;;;;;3;;;;;NAYELI LICETH CEDEÑO MERA;;ASIST. OPERAT.;;;;;;;;;;;;;
COAC SAN VALENTIN;;;;;;4;;;;;MARIELA FERNANDA GAIBOR GAIBOR;;OC;;;;;;;;;;;;;
COAC EL MOLINO;;;;;;3;;;;;KATHERINE ANDREA LLUGSHA AIÑA;;OC;;;;;;;;;;;;;
COAC FUTURO ESFUERZO Y DISCIPLINA;;;;;;4;;;;;JOSE VINICIO GUACHAMIN PARRA;;PROV. JUD;;;;;;;;;;;;;
COAC ARTESANOS;;;;;;2;;;;;VICTOR ALEJANDRO VITERI PROAÑO;;ASESOR LEGAL;;;;;;;;;;;;;
COAC OCCIDENTAL LTDA;;;;;;3;;;;;AMPARO ELIZABETH MORA MASAPANTA;;OC;;;;;;;;;;;;;
COAC UNION POPULAR;;;;;;4;;;;;ING VIRGINIA ANALUISA;;OC;;;;;;;;;;;;;
COAC ORDEN Y SEGURIDAD;;;;;;3;;;;;MINDA NARVÁEZ OMAYRA VERÓNICA;;OC;;;;;;;;;;;;;
COAC IMBABURA LTDA;;;;;;4;;;;;JESSICA MARIBEL CAMUENDO MORALES;;GERENTE G.;;;;;;;;;;;;;
COAC EL COMERCIO ;;;;;;;;;;;LORENA NATALIA OROZCO MERA;;ASESOR LEGAL;;;;;;;;;;;;;
COAC CORPORACION CENTRO;;;;;;3;;;;;WILLIAM ALONSO LOPEZ VELARDE;;OC;;;;;;;;;;;;;
COAC BANCO CENTRAL;;;;;;3;;;;;BRENDA ESTEFANY PALLO TOBAR;;SECRET. GEREN.;;;;;;;;;;;;;
COAS SAN ANTONIO IMBABURA;;;;;;2;;;;;ANALIA RAQUEL LOPEZ LOMAS;;ASIST. OPERAT.;;;;;;;;;;;;;
COAC SIERRA CENTRO LTDA;;;;;;3;;;;;WILSON ALEXIS AMANCHA SANCHEZ;;JEFE TECNOLOGIA;;;;;;;;;;;;;
COAC 27 DE NOVIEMBRE;;;;;;4;;;;;AIDA GLADYS GUAYAPACHA CRIOLLO;;JEFE DE OPERACIONES;;;;;;;;;;;;;
HATUN MUSKUY ENTIDAD FIANCIERA;;;;;;;;;;;LARA CACHIMUEL;;PROV. JUD;;;;;;;;;;;;;
COAC KULLKI WASI;;;;;;;;;;;NANCY YOLANDA QUINDIL UNAUCHO;;ASIST.LEGAL;;;;;;;;;;;;;
COAC GRUPO DIFARE;;;;;;3;;;;;NICOLE DANIELA ALVAREZ ARREAGA;;ASIST. ADMIN.;;;;;;;;;;;;;
COAC FUTURO LAMANENSE;;;;;;3;;;;;Bryan Moises Vega Oña;;Oficial de Seguridad de la Información;;;;;;;;;;;;;
COAC NUEVO AMANECER;;;;;;;;;;;YAJAIRA TOAPANTA;;OCS;;;;;;;;;;;;;
POR VERIFICAR INFO POR GLEN;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC 16 DE JULIO LTDA;;;;;;;;;;;JOSE EDUARDO FLORES PEÑAFIEL;;OC;;;;;;;;;;;;;
COAC 17 DE MARZO;;;;;;;;;;;LORENA MARITSA HINOSTROZA LOACHAMIN;;OC;;;;;;;;;;;;;
COAC COCA LTDA;;;;;;;;;;;XXXXXXXXXXXXXXX;;;;;;;;;;;;;;;
COAC ALIANZA DEL VALLE LTDA;;;;;;;;;;;XXXXXXXXXXXXXXX;;;;;;;;;;;;;;;
COAC CADMU MUJERES UNIDAS;;;;;;;;;;;UCACNOR;;;;;;;;;;;;;;;
COAC ANDALUCIA LTDA;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC COOPROGRESO;;;;;;;;;;;OJOOOOOO;;;;;;;;;;;;;;;
COAC PABLO MUÑOZ VEGA;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC ECUAFUTURO LTDA;;;;;;;;;;;EN ESPERA DE CONTRATO;;;;;;;;;;;;;;;
COAC SANTA ANITA;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC LA DOLOROSA LTDA;;;;;;;;;;;Mario Rolando Chela Curi;;;;;;;;;;;;;;;
COAC 15 DE ABRIL LTDA;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC ALLI TARPUK;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC EDUCADORES DE BOLIVAR LTDA;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC ACCION IMBABURAPAK;;;;;;;;;;;;;;;;;;;;;;;;;;
COAC VONNELAN LTDA;;;;;;;;;;;OJOOOOOO;;;;;;;;;;;;;;;
COAC ECUACREDITOS;;;;;;;;;;;OJO CANCELO CONTRATO ULTIMO DE JUNIO;;;;;;;;;;;;;;;
COAC IMBACOOP LTDA;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;
\.


--
-- TOC entry 3707 (class 0 OID 35157)
-- Dependencies: 225
-- Data for Name: contrataciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.contrataciones (id_contratacion, id_cooperativa, id_servicio, fecha_contratacion, fecha_inicio, fecha_fin, estado, observaciones, precio_individual, precio_grupal, iva, fecha_suscripcion, fecha_caducidad, fecha_desvinculacion, estado_servicio) FROM stdin;
6	1	1	2025-01-01	2025-01-01	2025-12-31	Activo	Contrato demo 1	100.00	450.00	12.00	2025-01-01	2025-12-31	\N	Activo
7	2	2	2025-02-15	2025-02-15	2025-12-31	Activo	Contrato demo 2	200.00	950.00	12.00	2025-02-15	2025-12-31	\N	Activo
8	3	3	2025-03-01	2025-03-01	2025-12-31	Suspendido	Contrato demo 3	300.00	1400.00	12.00	2025-03-01	2025-12-31	\N	Suspendido
9	4	4	2025-04-10	2025-04-10	2025-12-31	Pendiente	Contrato demo 4	120.00	550.00	12.00	2025-04-10	2025-12-31	\N	Pendiente
10	5	8	2025-05-20	2025-05-20	2025-12-31	Activo	Contrato demo 5	180.00	850.00	12.00	2025-05-20	2025-12-31	\N	Activo
\.


--
-- TOC entry 3709 (class 0 OID 35165)
-- Dependencies: 227
-- Data for Name: contrataciones_servicios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.contrataciones_servicios (id_contratacion, id_cooperativa, id_servicio, fecha_contratacion, valor_contratado, periodo_facturacion, activo, documento_contable, fecha_finalizacion, numero_licencias, fecha_ultimo_pago, estado_pago, licencias_pj_matrix, licencias_sic_matrix, licencias_gratis_matrix, licencias_pj_gratis, licencias_sic_gratis, licencias_sispla_matrix) FROM stdin;
\.


--
-- TOC entry 3711 (class 0 OID 35182)
-- Dependencies: 229
-- Data for Name: cooperativa_red; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cooperativa_red (id_cooperativa, codigo_red) FROM stdin;
\.


--
-- TOC entry 3712 (class 0 OID 35185)
-- Dependencies: 230
-- Data for Name: cooperativa_servicio; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cooperativa_servicio (id_cooperativa, id_servicio, activo, fecha_alta, fecha_baja, notas) FROM stdin;
17	1	t	2025-09-25	\N	\N
78	1	t	2025-09-29	\N	\N
\.


--
-- TOC entry 3713 (class 0 OID 35192)
-- Dependencies: 231
-- Data for Name: cooperativas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cooperativas (id_cooperativa, nombre, ruc, telefono, email, id_segmento, fecha_registro, activa, tipo_entidad, servicio_activo, telefono_fijo_1, telefono_fijo_2, telefono_movil, email2, pais, notas, red, provincia_id, canton_id) FROM stdin;
66	COAC COOPROGRESO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
73	HATUN MUSKUY ENTIDAD FINANCIERA	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
65	UCACNOR	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	union	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
72	COAC KULLKI WASI	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
74	COAC SAN JUAN DE COTOGCHOA	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
75	COAC ANDALUCIA LTDA	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
76	COAC ECUACREDITOS	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
77	COAC IMBACOOP LTDA	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
64	UPROCACH	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	empresa	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
27	COAC INTEGRACION SOLIDARIA	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
32	COAC INDIGENAS GALAPAGOS LTDA	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
36	COAC EMPRENDER LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
67	COAC ALIANZA DEL VALLE LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
63	RED DE ESTRUCTURAS FINANCIERAS POPULARES Y SOLIDARIAS EQUINOCCIO REDFINPSEQ (2130)	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	red	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
44	COAC FUTURO ESFUERZO Y DISCIPLINA	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
18	COAC MANANTIAL DE ORO	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	990102200	\N	Ecuador	\N	\N	19	\N
25	COAC HERMES GAIBOR	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	990874240	\N	Ecuador	\N	\N	5	\N
33	COAC SALITRE LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	985757696	\N	Ecuador	\N	\N	10	\N
38	COAC CAMARA DE COMERCIO LA JOYA DE LOS SACHAS	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	17	\N
17	COAC 16 DE JULIO LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	cooperativa	\N	\N	\N	0999261241	\N	Ecuador	\N	\N	19	\N
68	COAC COCA LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	17	\N
3	COAC EDUCADORES DE BOLIVAR	\N	3250525	\N	3	2025-09-01 15:53:29.230944	t	cooperativa	\N	3250525	\N	987845244	\N	Ecuador	\N	\N	2	16
61	COAC LA DOLOROSA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	964052222	\N	Ecuador	\N	\N	4	30
53	COAC SIERRA CENTRO LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	5	36
5	COAC ANDINA	\N	329943107	\N	2	2025-09-01 15:53:29.230944	t	COAC	\N	329943107	32802100	9854251911	\N	Ecuador	\N	\N	5	36
1	COAC SEÑOR DEL ARBOL	\N	987654321	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	987654321	98799257	991939235	\N	Ecuador	\N	\N	5	36
28	COAC ANDINA LTDA	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	5	36
56	COAC FUTURO LAMANENSE	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	37
30	COAC ICHUBAMBA LTDA	\N	\N	\N	5	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	5	39
10	COAC NUEVO AMANECER COTOPAXI	\N	32597709	\N	5	2025-09-01 15:53:29.230944	t	COAC	\N	32597709	998911061	988459290	\N	Ecuador	\N	\N	5	40
54	COAC 27 DE NOVIEMBRE	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	6	43
11	COAC RIOBAMBA LTDA.	\N	032962431	\N	1	2025-09-01 15:53:29.230944	t	COAC	\N	032962431	\N	983480014	\N	Ecuador	\N	\N	6	43
9	COAC SOL DE LOS ANDES LTDA	\N	32954855	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	32954855	\N	988482879	\N	Ecuador	\N	\N	6	43
29	COAC CHUNCHI LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	6	47
7	COAC CHUNCHI	\N	32936497	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	32936497	32936610	995256017	\N	Ecuador	\N	\N	6	47
39	COAC CÁMARA DE COMERCIO INDÍGENA DE GUAMOTE LTDA	\N	\N	\N	5	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	6	48
8	COAC GUAMOTE	\N	32916258	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	32916258	\N	987378595	\N	Ecuador	\N	\N	6	48
24	COAC MARCABELÍ LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	7	61
55	COAC GRUPO DIFARE	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	10	77
52	COAC SAN ANTONIO IMBABURA	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	11	102
16	COAC ARTESANOS	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	11	102
14	COAC SANTA ANITA LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	11	102
13	COAC SAN ANTONIO-IMBABURA	\N	65050304	\N	2	2025-09-01 15:53:29.230944	t	COAC	\N	65050304	\N	983170991	\N	Ecuador	\N	\N	11	102
12	COAC MUJERES UNIDAS "TANTANAKUSHKA WARMIKUNAPAC" CACMU	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	11	102
15	COAC SAN GABRIEL	\N	65359843	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	65359843	\N	987367241	\N	Ecuador	\N	\N	11	102
58	COAC ACCION IMBABURAPAK	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	11	105
48	COAC IMBABURA LTDA (FINANZACOOP)	\N	062922846	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	062922846	\N	\N	\N	Ecuador	\N	\N	11	105
34	COAC FORTUNA LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	12	108
6	COAC DE LA MICROEMPRESA LA FORTUNA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	12	108
40	COAC SAN JOSE LTDA	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	12	108
31	COAC SAN ANTONIO LTDA DE LOS RIOS	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	131
41	COAC MAGISTERIO MANABITA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	14	137
78	COAC 15 DE ABRIL LTDA	1390013678001	52633032	\N	1	2025-09-03 16:01:09.015036	t	cooperativa	\N	\N	\N	0000000000	\N	Ecuador	\N	\N	6	137
42	COAC CAMARA DE COMERCIO EL CARMEN LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	140
69	COAC ECUAFUTURO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	marina_freire@ecuafuturo.fin.ec	Ecuador	\N	\N	19	184
50	COAC CORPORACION CENTRO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
49	COAC EL COMERCIO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
47	COAC ORDEN Y SEGURIDAD	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
45	COAC OCCIDENTAL LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
62	EQUITY CASA DE VALORES	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	casa_valores	\N	\N	\N	958854579	\N	Ecuador	\N	\N	19	184
35	COAC HUAICANA	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
23	COAC EL MOLINO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
21	COAC 17 DE MARZO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
19	COAC SAN CRISTOBAL	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	987461741	\N	Ecuador	\N	\N	19	184
60	COAC PABLO MUÑOZ VEGA	\N	\N	\N	1	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
59	COAC EMPRENDEDORES COOPEMPRENDER LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
51	COAC BANCO CENTRAL	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
46	COAC UNION POPULAR	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
43	COAC SAN VALENTIN	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
57	COAC EMPLEADOS Y JUBILADOS BANCO CENTRAL DEL ECUADOR	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
26	COAC UNIVERSIDAD CATOLICA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
20	COAC ESPERANZA Y PROGRESO DEL VALLE	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
22	COAC COOPARTAMOS	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	19	184
70	COAC ALLI TARPURK LTDA	\N	22286525	\N	4	2025-09-03 13:12:34.179973	t	COAC	\N	22286525	\N	987811257	\N	Ecuador	\N	\N	\N	184
2	COAC VONNELAN	\N	22851527	\N	4	2025-09-01 15:53:29.230944	t	COAC	\N	22851527	\N	988386066	\N	Ecuador	\N	\N	19	190
37	COAC ASOCIACION MUTUALISTA DE AHORRO Y CRÉDITO PARA LA VIVIENDA AMBATO	\N	\N	\N	1	2025-09-01 15:53:29.230944	t	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	23	204
4	COAC COORAMBATO LTDA.	\N	32826057	\N	3	2025-09-01 15:53:29.230944	t	COAC	\N	32826057	\N	986201200	\N	Ecuador	\N	\N	23	204
\.


--
-- TOC entry 3748 (class 0 OID 35663)
-- Dependencies: 269
-- Data for Name: cooperativas_backup; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cooperativas_backup (id_cooperativa, nombre, ruc, telefono, email, id_segmento, fecha_registro, activa, provincia, canton, tipo_entidad, servicio_activo, telefono_fijo_1, telefono_fijo_2, telefono_movil, email2, pais, notas, red, provincia_id, canton_id) FROM stdin;
15	COAC SAN GABRIEL	\N	65359843	\N	3	2025-09-01 15:53:29.230944	t	Imbabura	Ibarra	COAC	\N	65359843	\N	987367241	\N	Ecuador	\N	\N	\N	\N
66	COAC COOPROGRESO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
73	HATUN MUSKUY ENTIDAD FINANCIERA	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
65	UCACNOR	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	\N	\N	union	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
72	COAC KULLKI WASI	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
74	COAC SAN JUAN DE COTOGCHOA	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
75	COAC ANDALUCIA LTDA	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
76	COAC ECUACREDITOS	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
77	COAC IMBACOOP LTDA	\N	\N	\N	\N	2025-09-03 13:12:34.179973	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
2	COAC VONNELAN	\N	22851527	\N	4	2025-09-01 15:53:29.230944	t	Pichincha	Rumiñahui	COAC	\N	22851527	\N	988386066	\N	Ecuador	\N	\N	\N	\N
64	UPROCACH	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	\N	\N	empresa	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
20	COAC ESPERANZA Y PROGRESO DEL VALLE	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
22	COAC COOPARTAMOS	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
26	COAC UNIVERSIDAD CATOLICA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
27	COAC INTEGRACION SOLIDARIA	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
28	COAC ANDINA LTDA	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	Cotopaxi	Latacunga	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
30	COAC ICHUBAMBA LTDA	\N	\N	\N	5	2025-09-01 15:53:29.230944	t	Cotopaxi	Pujilí	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
31	COAC SAN ANTONIO LTDA DE LOS RIOS	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	\N	Quevedo	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
32	COAC INDIGENAS GALAPAGOS LTDA	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
57	COAC EMPLEADOS Y JUBILADOS BANCO CENTRAL DEL ECUADOR	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
36	COAC EMPRENDER LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
67	COAC ALIANZA DEL VALLE LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
40	COAC SAN JOSE LTDA	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	Loja	Loja	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
63	RED DE ESTRUCTURAS FINANCIERAS POPULARES Y SOLIDARIAS EQUINOCCIO REDFINPSEQ (2130)	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	\N	\N	red	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
43	COAC SAN VALENTIN	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
44	COAC FUTURO ESFUERZO Y DISCIPLINA	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	\N	\N	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
46	COAC UNION POPULAR	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
51	COAC BANCO CENTRAL	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
59	COAC EMPRENDEDORES COOPEMPRENDER LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
60	COAC PABLO MUÑOZ VEGA	\N	\N	\N	1	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
70	COAC ALLI TARPURK LTDA	\N	22286525	\N	4	2025-09-03 13:12:34.179973	t	\N	Quito	COAC	\N	22286525	\N	987811257	\N	Ecuador	\N	\N	\N	\N
1	COAC SEÑOR DEL ARBOL	\N	987654321	\N	4	2025-09-01 15:53:29.230944	t	Cotopaxi	Latacunga	COAC	\N	987654321	98799257	991939235	\N	Ecuador	\N	\N	\N	\N
4	COAC COORAMBATO LTDA.	\N	32826057	\N	3	2025-09-01 15:53:29.230944	t	Tungurahua	Ambato	COAC	\N	32826057	\N	986201200	\N	Ecuador	\N	\N	\N	\N
5	COAC ANDINA	\N	329943107	\N	2	2025-09-01 15:53:29.230944	t	Cotopaxi	Latacunga	COAC	\N	329943107	32802100	9854251911	\N	Ecuador	\N	\N	\N	\N
6	COAC DE LA MICROEMPRESA LA FORTUNA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Loja	Loja	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
7	COAC CHUNCHI	\N	32936497	\N	3	2025-09-01 15:53:29.230944	t	Chimborazo	Chunchi	COAC	\N	32936497	32936610	995256017	\N	Ecuador	\N	\N	\N	\N
8	COAC GUAMOTE	\N	32916258	\N	3	2025-09-01 15:53:29.230944	t	Chimborazo	Guamote	COAC	\N	32916258	\N	987378595	\N	Ecuador	\N	\N	\N	\N
9	COAC SOL DE LOS ANDES LTDA	\N	32954855	\N	3	2025-09-01 15:53:29.230944	t	Chimborazo	Riobamba	COAC	\N	32954855	\N	988482879	\N	Ecuador	\N	\N	\N	\N
10	COAC NUEVO AMANECER COTOPAXI	\N	32597709	\N	5	2025-09-01 15:53:29.230944	t	Cotopaxi	Salcedo	COAC	\N	32597709	998911061	988459290	\N	Ecuador	\N	\N	\N	\N
11	COAC RIOBAMBA LTDA.	\N	032962431	\N	1	2025-09-01 15:53:29.230944	t	Chimborazo	Riobamba	COAC	\N	032962431	\N	983480014	\N	Ecuador	\N	\N	\N	\N
12	COAC MUJERES UNIDAS "TANTANAKUSHKA WARMIKUNAPAC" CACMU	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Imbabura	Ibarra	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
13	COAC SAN ANTONIO-IMBABURA	\N	65050304	\N	2	2025-09-01 15:53:29.230944	t	Imbabura	Ibarra	COAC	\N	65050304	\N	983170991	\N	Ecuador	\N	\N	\N	\N
14	COAC SANTA ANITA LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Imbabura	Ibarra	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
16	COAC ARTESANOS	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	Imbabura	Ibarra	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
18	COAC MANANTIAL DE ORO	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	Pichincha	Machachi	COAC	\N	\N	\N	990102200	\N	Ecuador	\N	\N	\N	\N
19	COAC SAN CRISTOBAL	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	987461741	\N	Ecuador	\N	\N	\N	\N
21	COAC 17 DE MARZO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
23	COAC EL MOLINO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
24	COAC MARCABELÍ LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	El Oro	Marcabelí	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
25	COAC HERMES GAIBOR	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Cotopaxi	Moraspungo	COAC	\N	\N	\N	990874240	\N	Ecuador	\N	\N	\N	\N
29	COAC CHUNCHI LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Chimborazo	Chunchi	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
33	COAC SALITRE LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Guayas	Salitre	COAC	\N	\N	\N	985757696	\N	Ecuador	\N	\N	\N	\N
34	COAC FORTUNA LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Loja	Loja	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
35	COAC HUAICANA	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
37	COAC ASOCIACION MUTUALISTA DE AHORRO Y CRÉDITO PARA LA VIVIENDA AMBATO	\N	\N	\N	1	2025-09-01 15:53:29.230944	t	Tungurahua	Ambato	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
38	COAC CAMARA DE COMERCIO LA JOYA DE LOS SACHAS	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Orellana	El Coca	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
17	COAC 16 DE JULIO LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Ascazubi	cooperativa	\N	\N	\N	0999261241	\N	Ecuador	\N	\N	\N	\N
39	COAC CÁMARA DE COMERCIO INDÍGENA DE GUAMOTE LTDA	\N	\N	\N	5	2025-09-01 15:53:29.230944	t	Chimborazo	Guamote	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
62	EQUITY CASA DE VALORES	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	Pichincha	Quito	casa_valores	\N	\N	\N	958854579	\N	Ecuador	\N	\N	\N	\N
41	COAC MAGISTERIO MANABITA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Manabí	Portoviejo	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
42	COAC CAMARA DE COMERCIO EL CARMEN LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	\N	El Carmen	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
45	COAC OCCIDENTAL LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
47	COAC ORDEN Y SEGURIDAD	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
48	COAC IMBABURA LTDA (FINANZACOOP)	\N	062922846	\N	4	2025-09-01 15:53:29.230944	t	Imbabura	Otavalo	COAC	\N	062922846	\N	\N	\N	Ecuador	\N	\N	\N	\N
49	COAC EL COMERCIO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
50	COAC CORPORACION CENTRO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
52	COAC SAN ANTONIO IMBABURA	\N	\N	\N	2	2025-09-01 15:53:29.230944	t	Imbabura	Ibarra	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
53	COAC SIERRA CENTRO LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Cotopaxi	Latacunga	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
54	COAC 27 DE NOVIEMBRE	\N	\N	\N	4	2025-09-01 15:53:29.230944	t	Chimborazo	Riobamba	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
55	COAC GRUPO DIFARE	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Guayas	Guayaquil	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
56	COAC FUTURO LAMANENSE	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	\N	La Mana	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
58	COAC ACCION IMBABURAPAK	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Imbabura	Otavalo	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
61	COAC LA DOLOROSA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Carchi	Tulcán	COAC	\N	\N	\N	964052222	\N	Ecuador	\N	\N	\N	\N
68	COAC COCA LTDA	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Orellana	El Coca	COAC	\N	\N	\N	\N	\N	Ecuador	\N	\N	\N	\N
69	COAC ECUAFUTURO	\N	\N	\N	3	2025-09-01 15:53:29.230944	t	Pichincha	Quito	COAC	\N	\N	\N	\N	marina_freire@ecuafuturo.fin.ec	Ecuador	\N	\N	\N	\N
3	COAC EDUCADORES DE BOLIVAR	\N	3250525	\N	3	2025-09-01 15:53:29.230944	t	Bolívar	Guaranda	cooperativa	\N	3250525	\N	987845244	\N	Ecuador	\N	\N	\N	\N
78	COAC 15 DE ABRIL LTDA	1390013678001	52633032	\N	1	2025-09-03 16:01:09.015036	t	Manabí	Portoviejo	cooperativa	\N	\N	\N	0000000000	\N	Ecuador	\N	\N	6	\N
\.


--
-- TOC entry 3715 (class 0 OID 35206)
-- Dependencies: 233
-- Data for Name: datos_facturacion; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.datos_facturacion (id_facturacion, id_cooperativa, direccion, provincia, canton, email1, email2, email3, email4, email5, tel_fijo1, tel_fijo2, tel_fijo3, tel_cel1, tel_cel2, tel_cel3, contabilidad_nombre, contabilidad_telefono, fecha_registro, provincia_id, canton_id) FROM stdin;
\.


--
-- TOC entry 3717 (class 0 OID 35213)
-- Dependencies: 235
-- Data for Name: equipos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.equipos (id_equipo, codigo_patrimonial, nombre_equipo, tipo, marca, modelo, id_usuario_asignado, fecha_adquisicion, garantia_hasta, especificaciones, estado) FROM stdin;
\.


--
-- TOC entry 3719 (class 0 OID 35220)
-- Dependencies: 237
-- Data for Name: incidencias_comercial; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.incidencias_comercial (id_incidencia, id_cooperativa, asunto, descripcion, prioridad, estado, creado_por, id_ticket, created_at) FROM stdin;
1	78	321312	321312	Medio	Enviado	9	12	2025-09-08 15:11:51.606564
\.


--
-- TOC entry 3721 (class 0 OID 35231)
-- Dependencies: 239
-- Data for Name: incidencias_vistas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.incidencias_vistas (id_usuario, id_incidencia, visto_cerrada_at) FROM stdin;
9	1	2025-08-28 16:27:46.84396
\.


--
-- TOC entry 3722 (class 0 OID 35235)
-- Dependencies: 240
-- Data for Name: info_contabilidad; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.info_contabilidad (id_info, id_cooperativa, responsable_contable, email_contable, telefono_contable, ruc_contabilidad, direccion_contabilidad, fecha_actualizacion) FROM stdin;
\.


--
-- TOC entry 3724 (class 0 OID 35242)
-- Dependencies: 242
-- Data for Name: instalaciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.instalaciones (id_instalacion, id_contratacion, id_usuario_tecnico, fecha_instalacion, fecha_completada, estado, observaciones) FROM stdin;
\.


--
-- TOC entry 3726 (class 0 OID 35249)
-- Dependencies: 244
-- Data for Name: listas_control; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.listas_control (id_lista_control, id_cooperativa, fecha_actualizacion, responsable, observaciones) FROM stdin;
\.


--
-- TOC entry 3728 (class 0 OID 35255)
-- Dependencies: 246
-- Data for Name: pagos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pagos (id_pago, id_contratacion, monto, fecha_pago, metodo_pago, comprobante, estado, observaciones) FROM stdin;
1	\N	120.50	2025-08-25	Transferencia	comprobante_1.pdf	Completado	\N
2	\N	250.75	2025-08-26	Efectivo	comprobante_2.pdf	Pendiente	\N
3	\N	300.00	2025-08-27	Cheque	comprobante_3.pdf	Completado	\N
4	\N	180.20	2025-08-28	Tarjeta de crédito	comprobante_4.pdf	Rechazado	\N
5	\N	500.00	2025-08-29	Transferencia	comprobante_5.pdf	Completado	\N
\.


--
-- TOC entry 3730 (class 0 OID 35261)
-- Dependencies: 248
-- Data for Name: personal_cooperativa; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.personal_cooperativa (id_personal, id_cooperativa, nombre, cargo, telefono, email, departamento) FROM stdin;
\.


--
-- TOC entry 3732 (class 0 OID 35265)
-- Dependencies: 250
-- Data for Name: provincia; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.provincia (id, nombre) FROM stdin;
1	Azuay
2	Bolívar
3	Cañar
4	Carchi
5	Cotopaxi
6	Chimborazo
7	El Oro
8	Esmeraldas
9	Galápagos
10	Guayas
11	Imbabura
12	Loja
13	Los Ríos
14	Manabí
15	Morona Santiago
16	Napo
17	Orellana
18	Pastaza
19	Pichincha
20	Santa Elena
21	Santo Domingo de los Tsáchilas
22	Sucumbíos
23	Tungurahua
24	Zamora Chinchipe
\.


--
-- TOC entry 3734 (class 0 OID 35269)
-- Dependencies: 252
-- Data for Name: red; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.red (codigo, nombre) FROM stdin;
UPROCACHT	UPROCACHT
UCACNOR	UCACNOR
FECOAC	FECOAC
\.


--
-- TOC entry 3735 (class 0 OID 35272)
-- Dependencies: 253
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.roles (id_rol, nombre_rol, descripcion) FROM stdin;
1	administrador	Administrador
3	contabilidad	Departamento Contabilidad
2	comercial	Departamento Comercial
4	sistemas	Departamento Sistemas
5	cumplimiento	Departamento Cumplimiento
6	providencias	Departamento Providencias/SIC
\.


--
-- TOC entry 3737 (class 0 OID 35278)
-- Dependencies: 255
-- Data for Name: segmentos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.segmentos (id_segmento, nombre_segmento, descripcion) FROM stdin;
1	Segmento 1	\N
2	Segmento 2	\N
4	Segmento 4	\N
5	Segmento 5	\N
3	Segmento 3	\N
\.


--
-- TOC entry 3739 (class 0 OID 35284)
-- Dependencies: 257
-- Data for Name: servicios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.servicios (id_servicio, nombre_servicio, descripcion, activo) FROM stdin;
8	CORE FINANCIERO	Core Financiero	t
1	Matrix	MATRIX	t
4	SISPLA	SISPLA	t
2	PJ	Providencias judiciales	t
3	SIC	Sistema de información de clientes	t
\.


--
-- TOC entry 3741 (class 0 OID 35291)
-- Dependencies: 259
-- Data for Name: ticket_historial; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ticket_historial (id_historial, id_ticket, fecha_cambio, campo_modificado, valor_anterior, valor_nuevo, id_usuario) FROM stdin;
\.


--
-- TOC entry 3743 (class 0 OID 35298)
-- Dependencies: 261
-- Data for Name: tickets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tickets (id_ticket, titulo, descripcion, id_usuario_reporta, id_equipo, id_categoria, prioridad, estado, fecha_apertura, fecha_cierre, solucion, id_tecnico_asignado) FROM stdin;
6	Prueba 2	dsbtr	13	\N	9	Crítico	Abierto	2025-08-22 15:32:22.074792	\N	\N	\N
1	Instalación PJ	Instalación del nuevo sistema de Providencias Judiciales a COAC Anda lucia	13	\N	9	Crítico	Abierto	2025-06-20 15:58:12.485914	\N	\N	\N
9	Prueba 1	bfddsv	13	\N	1	Medio	Cerrado	2025-08-25 12:53:43.898553	2025-08-25 12:54:20.75008	\N	16
10	111	urgente	9	\N	8	Crítico	Abierto	2025-08-26 15:19:43.96657	\N	\N	\N
11	P	ddd	9	\N	8	Crítico	Abierto	2025-08-28 16:16:18.657312	\N	\N	\N
12	321312	321312	9	\N	8	Medio	Abierto	2025-09-08 15:11:51.606564	\N	\N	\N
\.


--
-- TOC entry 3745 (class 0 OID 35307)
-- Dependencies: 263
-- Data for Name: usuario_categorias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuario_categorias (id_usuario, id_categoria) FROM stdin;
\.


--
-- TOC entry 3746 (class 0 OID 35310)
-- Dependencies: 264
-- Data for Name: usuarios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuarios (id_usuario, username, password_md5, id_rol, nombre_completo, email, activo, fecha_creacion, departamento) FROM stdin;
1	admin	21232f297a57a5a743894a0e4a801fc3	1	Renato Joel	renatojoel11@gmail.com	t	2025-04-22 00:00:00	\N
14	Cumplimiento	6579f08fa9dd51234fd90a8ee0212302	5	Cumplimientos	cumplimiento@vipg.com	t	2025-07-04 10:42:08.792303	\N
13	sistemas	102ddaf691e1615d5dacd4c86299bfa4	4	Soporte Sistemas	asistencia.tecnica@vipg.com	t	2025-06-20 11:55:36.354497	\N
11	contabilidad	d5a9e0f7baefc827e7ac792cc2ba3439	3	contabilidad	contabilidad@gmail.com	t	2025-04-28 12:31:01.160634	\N
15	Sugerencias	8370e358c73f99a7f89d9e288d9ad524	4	Sugerencias	sugerencias@vipg.com	t	2025-07-04 10:46:27.975379	\N
9	comercial	4072c1c3f468878a7d48dd7a4564cb57	2	Joel Alvarado	joel@gmail.com	t	2025-04-23 12:15:28.99413	\N
16	Dennis	c3875d07f44c422f3b3bc019c23e16ae	4	Dennis Andrade	denisandradeg@gmail.com	t	2025-08-21 15:12:01.528089	\N
\.


--
-- TOC entry 3783 (class 0 OID 0)
-- Dependencies: 212
-- Name: agenda_contactos_id_evento_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.agenda_contactos_id_evento_seq', 1, true);


--
-- TOC entry 3784 (class 0 OID 0)
-- Dependencies: 213
-- Name: agenda_id_agenda_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.agenda_id_agenda_seq', 1, false);


--
-- TOC entry 3785 (class 0 OID 0)
-- Dependencies: 215
-- Name: asistentes_capacitacion_id_asistente_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asistentes_capacitacion_id_asistente_seq', 1, false);


--
-- TOC entry 3786 (class 0 OID 0)
-- Dependencies: 217
-- Name: canton_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.canton_id_seq', 221, true);


--
-- TOC entry 3787 (class 0 OID 0)
-- Dependencies: 219
-- Name: capacitaciones_id_capacitacion_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.capacitaciones_id_capacitacion_seq', 1, false);


--
-- TOC entry 3788 (class 0 OID 0)
-- Dependencies: 221
-- Name: capacitaciones_providencias_id_capacitacion_providencia_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.capacitaciones_providencias_id_capacitacion_providencia_seq', 1, false);


--
-- TOC entry 3789 (class 0 OID 0)
-- Dependencies: 223
-- Name: categorias_id_categoria_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.categorias_id_categoria_seq', 9, true);


--
-- TOC entry 3790 (class 0 OID 0)
-- Dependencies: 226
-- Name: contrataciones_id_contratacion_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.contrataciones_id_contratacion_seq', 10, true);


--
-- TOC entry 3791 (class 0 OID 0)
-- Dependencies: 228
-- Name: contrataciones_servicios_id_contratacion_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.contrataciones_servicios_id_contratacion_seq', 1, false);


--
-- TOC entry 3792 (class 0 OID 0)
-- Dependencies: 232
-- Name: cooperativas_id_cooperativa_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cooperativas_id_cooperativa_seq', 141, true);


--
-- TOC entry 3793 (class 0 OID 0)
-- Dependencies: 234
-- Name: datos_facturacion_id_facturacion_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.datos_facturacion_id_facturacion_seq', 1, false);


--
-- TOC entry 3794 (class 0 OID 0)
-- Dependencies: 236
-- Name: equipos_id_equipo_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.equipos_id_equipo_seq', 1, false);


--
-- TOC entry 3795 (class 0 OID 0)
-- Dependencies: 238
-- Name: incidencias_comercial_id_incidencia_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.incidencias_comercial_id_incidencia_seq', 1, true);


--
-- TOC entry 3796 (class 0 OID 0)
-- Dependencies: 241
-- Name: info_contabilidad_id_info_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.info_contabilidad_id_info_seq', 1, false);


--
-- TOC entry 3797 (class 0 OID 0)
-- Dependencies: 243
-- Name: instalaciones_id_instalacion_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.instalaciones_id_instalacion_seq', 1, false);


--
-- TOC entry 3798 (class 0 OID 0)
-- Dependencies: 245
-- Name: listas_control_id_lista_control_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.listas_control_id_lista_control_seq', 1, false);


--
-- TOC entry 3799 (class 0 OID 0)
-- Dependencies: 247
-- Name: pagos_id_pago_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pagos_id_pago_seq', 5, true);


--
-- TOC entry 3800 (class 0 OID 0)
-- Dependencies: 249
-- Name: personal_cooperativa_id_personal_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.personal_cooperativa_id_personal_seq', 1, false);


--
-- TOC entry 3801 (class 0 OID 0)
-- Dependencies: 251
-- Name: provincia_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.provincia_id_seq', 1, false);


--
-- TOC entry 3802 (class 0 OID 0)
-- Dependencies: 254
-- Name: roles_id_rol_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_rol_seq', 1, false);


--
-- TOC entry 3803 (class 0 OID 0)
-- Dependencies: 256
-- Name: segmentos_id_segmento_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.segmentos_id_segmento_seq', 16, true);


--
-- TOC entry 3804 (class 0 OID 0)
-- Dependencies: 258
-- Name: servicios_id_servicio_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.servicios_id_servicio_seq', 12, true);


--
-- TOC entry 3805 (class 0 OID 0)
-- Dependencies: 260
-- Name: ticket_historial_id_historial_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.ticket_historial_id_historial_seq', 1, false);


--
-- TOC entry 3806 (class 0 OID 0)
-- Dependencies: 262
-- Name: tickets_id_ticket_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tickets_id_ticket_seq', 12, true);


--
-- TOC entry 3807 (class 0 OID 0)
-- Dependencies: 265
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuarios_id_usuario_seq', 16, true);


--
-- TOC entry 3422 (class 2606 OID 35346)
-- Name: agenda_contactos agenda_contactos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agenda_contactos
    ADD CONSTRAINT agenda_contactos_pkey PRIMARY KEY (id_evento);


--
-- TOC entry 3420 (class 2606 OID 35348)
-- Name: agenda agenda_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agenda
    ADD CONSTRAINT agenda_pkey PRIMARY KEY (id_agenda);


--
-- TOC entry 3426 (class 2606 OID 35350)
-- Name: asistentes_capacitacion asistentes_capacitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistentes_capacitacion
    ADD CONSTRAINT asistentes_capacitacion_pkey PRIMARY KEY (id_asistente);


--
-- TOC entry 3428 (class 2606 OID 35352)
-- Name: canton canton_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.canton
    ADD CONSTRAINT canton_pkey PRIMARY KEY (id);


--
-- TOC entry 3430 (class 2606 OID 35354)
-- Name: canton canton_provincia_id_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.canton
    ADD CONSTRAINT canton_provincia_id_nombre_key UNIQUE (provincia_id, nombre);


--
-- TOC entry 3433 (class 2606 OID 35356)
-- Name: capacitaciones capacitaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capacitaciones
    ADD CONSTRAINT capacitaciones_pkey PRIMARY KEY (id_capacitacion);


--
-- TOC entry 3435 (class 2606 OID 35358)
-- Name: capacitaciones_providencias capacitaciones_providencias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capacitaciones_providencias
    ADD CONSTRAINT capacitaciones_providencias_pkey PRIMARY KEY (id_capacitacion_providencia);


--
-- TOC entry 3437 (class 2606 OID 35360)
-- Name: categorias categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_pkey PRIMARY KEY (id_categoria);


--
-- TOC entry 3439 (class 2606 OID 35362)
-- Name: contrataciones contrataciones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contrataciones
    ADD CONSTRAINT contrataciones_pkey PRIMARY KEY (id_contratacion);


--
-- TOC entry 3441 (class 2606 OID 35364)
-- Name: contrataciones_servicios contrataciones_servicios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contrataciones_servicios
    ADD CONSTRAINT contrataciones_servicios_pkey PRIMARY KEY (id_contratacion);


--
-- TOC entry 3443 (class 2606 OID 35366)
-- Name: cooperativa_red cooperativa_red_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativa_red
    ADD CONSTRAINT cooperativa_red_pkey PRIMARY KEY (id_cooperativa, codigo_red);


--
-- TOC entry 3445 (class 2606 OID 35368)
-- Name: cooperativa_servicio cooperativa_servicio_pk; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativa_servicio
    ADD CONSTRAINT cooperativa_servicio_pk PRIMARY KEY (id_cooperativa, id_servicio);


--
-- TOC entry 3447 (class 2606 OID 35370)
-- Name: cooperativas cooperativas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativas
    ADD CONSTRAINT cooperativas_pkey PRIMARY KEY (id_cooperativa);


--
-- TOC entry 3449 (class 2606 OID 35372)
-- Name: cooperativas cooperativas_ruc_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativas
    ADD CONSTRAINT cooperativas_ruc_key UNIQUE (ruc);


--
-- TOC entry 3454 (class 2606 OID 35374)
-- Name: datos_facturacion datos_facturacion_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.datos_facturacion
    ADD CONSTRAINT datos_facturacion_pkey PRIMARY KEY (id_facturacion);


--
-- TOC entry 3456 (class 2606 OID 35376)
-- Name: equipos equipos_codigo_patrimonial_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.equipos
    ADD CONSTRAINT equipos_codigo_patrimonial_key UNIQUE (codigo_patrimonial);


--
-- TOC entry 3458 (class 2606 OID 35378)
-- Name: equipos equipos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.equipos
    ADD CONSTRAINT equipos_pkey PRIMARY KEY (id_equipo);


--
-- TOC entry 3462 (class 2606 OID 35380)
-- Name: incidencias_comercial incidencias_comercial_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_comercial
    ADD CONSTRAINT incidencias_comercial_pkey PRIMARY KEY (id_incidencia);


--
-- TOC entry 3464 (class 2606 OID 35382)
-- Name: incidencias_vistas incidencias_vistas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_vistas
    ADD CONSTRAINT incidencias_vistas_pkey PRIMARY KEY (id_usuario, id_incidencia);


--
-- TOC entry 3466 (class 2606 OID 35384)
-- Name: info_contabilidad info_contabilidad_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.info_contabilidad
    ADD CONSTRAINT info_contabilidad_pkey PRIMARY KEY (id_info);


--
-- TOC entry 3470 (class 2606 OID 35386)
-- Name: instalaciones instalaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.instalaciones
    ADD CONSTRAINT instalaciones_pkey PRIMARY KEY (id_instalacion);


--
-- TOC entry 3472 (class 2606 OID 35388)
-- Name: listas_control listas_control_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listas_control
    ADD CONSTRAINT listas_control_pkey PRIMARY KEY (id_lista_control);


--
-- TOC entry 3474 (class 2606 OID 35390)
-- Name: pagos pagos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_pkey PRIMARY KEY (id_pago);


--
-- TOC entry 3476 (class 2606 OID 35392)
-- Name: personal_cooperativa personal_cooperativa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_cooperativa
    ADD CONSTRAINT personal_cooperativa_pkey PRIMARY KEY (id_personal);


--
-- TOC entry 3478 (class 2606 OID 35394)
-- Name: provincia provincia_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.provincia
    ADD CONSTRAINT provincia_nombre_key UNIQUE (nombre);


--
-- TOC entry 3480 (class 2606 OID 35396)
-- Name: provincia provincia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.provincia
    ADD CONSTRAINT provincia_pkey PRIMARY KEY (id);


--
-- TOC entry 3482 (class 2606 OID 35398)
-- Name: red red_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.red
    ADD CONSTRAINT red_pkey PRIMARY KEY (codigo);


--
-- TOC entry 3484 (class 2606 OID 35400)
-- Name: roles roles_nombre_rol_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nombre_rol_key UNIQUE (nombre_rol);


--
-- TOC entry 3486 (class 2606 OID 35402)
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id_rol);


--
-- TOC entry 3488 (class 2606 OID 35404)
-- Name: segmentos segmentos_nombre_segmento_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.segmentos
    ADD CONSTRAINT segmentos_nombre_segmento_key UNIQUE (nombre_segmento);


--
-- TOC entry 3490 (class 2606 OID 35406)
-- Name: segmentos segmentos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.segmentos
    ADD CONSTRAINT segmentos_pkey PRIMARY KEY (id_segmento);


--
-- TOC entry 3493 (class 2606 OID 35408)
-- Name: servicios servicios_nombre_servicio_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servicios
    ADD CONSTRAINT servicios_nombre_servicio_key UNIQUE (nombre_servicio);


--
-- TOC entry 3495 (class 2606 OID 35410)
-- Name: servicios servicios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servicios
    ADD CONSTRAINT servicios_pkey PRIMARY KEY (id_servicio);


--
-- TOC entry 3497 (class 2606 OID 35412)
-- Name: ticket_historial ticket_historial_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_historial
    ADD CONSTRAINT ticket_historial_pkey PRIMARY KEY (id_historial);


--
-- TOC entry 3499 (class 2606 OID 35414)
-- Name: tickets tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_pkey PRIMARY KEY (id_ticket);


--
-- TOC entry 3468 (class 2606 OID 35416)
-- Name: info_contabilidad uk_info_contabilidad_cooperativa; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.info_contabilidad
    ADD CONSTRAINT uk_info_contabilidad_cooperativa UNIQUE (id_cooperativa);


--
-- TOC entry 3501 (class 2606 OID 35418)
-- Name: usuario_categorias usuario_categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario_categorias
    ADD CONSTRAINT usuario_categorias_pkey PRIMARY KEY (id_usuario, id_categoria);


--
-- TOC entry 3503 (class 2606 OID 35420)
-- Name: usuarios usuarios_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_email_key UNIQUE (email);


--
-- TOC entry 3505 (class 2606 OID 35422)
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id_usuario);


--
-- TOC entry 3507 (class 2606 OID 35424)
-- Name: usuarios usuarios_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_username_key UNIQUE (username);


--
-- TOC entry 3416 (class 1259 OID 35425)
-- Name: agenda_entidad_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX agenda_entidad_idx ON public.agenda USING btree (id_entidad);


--
-- TOC entry 3417 (class 1259 OID 35426)
-- Name: agenda_estado_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX agenda_estado_idx ON public.agenda USING btree (estado);


--
-- TOC entry 3418 (class 1259 OID 35427)
-- Name: agenda_fecha_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX agenda_fecha_idx ON public.agenda USING btree (fecha);


--
-- TOC entry 3423 (class 1259 OID 35428)
-- Name: idx_agenda_contactos_coop; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_agenda_contactos_coop ON public.agenda_contactos USING btree (id_cooperativa);


--
-- TOC entry 3424 (class 1259 OID 35429)
-- Name: idx_agenda_contactos_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_agenda_contactos_fecha ON public.agenda_contactos USING btree (fecha_evento);


--
-- TOC entry 3450 (class 1259 OID 35430)
-- Name: idx_coops_nombre; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_coops_nombre ON public.cooperativas USING btree (nombre);


--
-- TOC entry 3451 (class 1259 OID 35431)
-- Name: idx_coops_ruc; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_coops_ruc ON public.cooperativas USING btree (ruc);


--
-- TOC entry 3459 (class 1259 OID 35432)
-- Name: idx_incidencias_comercial_coop; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_incidencias_comercial_coop ON public.incidencias_comercial USING btree (id_cooperativa);


--
-- TOC entry 3460 (class 1259 OID 35433)
-- Name: idx_incidencias_comercial_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_incidencias_comercial_estado ON public.incidencias_comercial USING btree (estado);


--
-- TOC entry 3491 (class 1259 OID 35434)
-- Name: idx_servicios_nombre; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_servicios_nombre ON public.servicios USING btree (nombre_servicio);


--
-- TOC entry 3431 (class 1259 OID 35435)
-- Name: ix_canton_provincia; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_canton_provincia ON public.canton USING btree (provincia_id, nombre);


--
-- TOC entry 3452 (class 1259 OID 35436)
-- Name: uq_cooperativas_ruc_notnull; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX uq_cooperativas_ruc_notnull ON public.cooperativas USING btree (ruc) WHERE (ruc IS NOT NULL);


--
-- TOC entry 3509 (class 2606 OID 35437)
-- Name: agenda_contactos agenda_contactos_creado_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agenda_contactos
    ADD CONSTRAINT agenda_contactos_creado_por_fkey FOREIGN KEY (creado_por) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 3510 (class 2606 OID 35442)
-- Name: agenda_contactos agenda_contactos_id_cooperativa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agenda_contactos
    ADD CONSTRAINT agenda_contactos_id_cooperativa_fkey FOREIGN KEY (id_cooperativa) REFERENCES public.cooperativas(id_cooperativa) ON DELETE SET NULL;


--
-- TOC entry 3508 (class 2606 OID 35447)
-- Name: agenda agenda_id_entidad_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agenda
    ADD CONSTRAINT agenda_id_entidad_fkey FOREIGN KEY (id_entidad) REFERENCES public.cooperativas(id_cooperativa) ON DELETE SET NULL;


--
-- TOC entry 3511 (class 2606 OID 35452)
-- Name: asistentes_capacitacion asistentes_capacitacion_id_capacitacion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistentes_capacitacion
    ADD CONSTRAINT asistentes_capacitacion_id_capacitacion_fkey FOREIGN KEY (id_capacitacion) REFERENCES public.capacitaciones(id_capacitacion);


--
-- TOC entry 3512 (class 2606 OID 35457)
-- Name: asistentes_capacitacion asistentes_capacitacion_id_personal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistentes_capacitacion
    ADD CONSTRAINT asistentes_capacitacion_id_personal_fkey FOREIGN KEY (id_personal) REFERENCES public.personal_cooperativa(id_personal);


--
-- TOC entry 3513 (class 2606 OID 35462)
-- Name: canton canton_provincia_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.canton
    ADD CONSTRAINT canton_provincia_id_fkey FOREIGN KEY (provincia_id) REFERENCES public.provincia(id) ON DELETE RESTRICT;


--
-- TOC entry 3514 (class 2606 OID 35467)
-- Name: capacitaciones capacitaciones_id_contratacion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capacitaciones
    ADD CONSTRAINT capacitaciones_id_contratacion_fkey FOREIGN KEY (id_contratacion) REFERENCES public.contrataciones(id_contratacion);


--
-- TOC entry 3515 (class 2606 OID 35472)
-- Name: capacitaciones capacitaciones_id_usuario_capacitador_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capacitaciones
    ADD CONSTRAINT capacitaciones_id_usuario_capacitador_fkey FOREIGN KEY (id_usuario_capacitador) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 3516 (class 2606 OID 35477)
-- Name: capacitaciones_providencias capacitaciones_providencias_id_capacitacion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capacitaciones_providencias
    ADD CONSTRAINT capacitaciones_providencias_id_capacitacion_fkey FOREIGN KEY (id_capacitacion) REFERENCES public.capacitaciones(id_capacitacion);


--
-- TOC entry 3517 (class 2606 OID 35482)
-- Name: contrataciones contrataciones_id_cooperativa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contrataciones
    ADD CONSTRAINT contrataciones_id_cooperativa_fkey FOREIGN KEY (id_cooperativa) REFERENCES public.cooperativas(id_cooperativa);


--
-- TOC entry 3518 (class 2606 OID 35487)
-- Name: contrataciones contrataciones_id_servicio_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contrataciones
    ADD CONSTRAINT contrataciones_id_servicio_fkey FOREIGN KEY (id_servicio) REFERENCES public.servicios(id_servicio);


--
-- TOC entry 3519 (class 2606 OID 35492)
-- Name: contrataciones_servicios contrataciones_servicios_id_cooperativa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contrataciones_servicios
    ADD CONSTRAINT contrataciones_servicios_id_cooperativa_fkey FOREIGN KEY (id_cooperativa) REFERENCES public.cooperativas(id_cooperativa) ON DELETE CASCADE;


--
-- TOC entry 3520 (class 2606 OID 35497)
-- Name: contrataciones_servicios contrataciones_servicios_id_servicio_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contrataciones_servicios
    ADD CONSTRAINT contrataciones_servicios_id_servicio_fkey FOREIGN KEY (id_servicio) REFERENCES public.servicios(id_servicio);


--
-- TOC entry 3521 (class 2606 OID 35502)
-- Name: cooperativa_red cooperativa_red_red_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativa_red
    ADD CONSTRAINT cooperativa_red_red_fk FOREIGN KEY (codigo_red) REFERENCES public.red(codigo) ON UPDATE CASCADE;


--
-- TOC entry 3522 (class 2606 OID 35507)
-- Name: cooperativa_servicio cooperativa_servicio_id_cooperativa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativa_servicio
    ADD CONSTRAINT cooperativa_servicio_id_cooperativa_fkey FOREIGN KEY (id_cooperativa) REFERENCES public.cooperativas(id_cooperativa) ON DELETE CASCADE;


--
-- TOC entry 3523 (class 2606 OID 35512)
-- Name: cooperativa_servicio cooperativa_servicio_id_servicio_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativa_servicio
    ADD CONSTRAINT cooperativa_servicio_id_servicio_fkey FOREIGN KEY (id_servicio) REFERENCES public.servicios(id_servicio);


--
-- TOC entry 3524 (class 2606 OID 35517)
-- Name: cooperativas cooperativas_canton_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativas
    ADD CONSTRAINT cooperativas_canton_fk FOREIGN KEY (canton_id) REFERENCES public.canton(id);


--
-- TOC entry 3525 (class 2606 OID 35522)
-- Name: cooperativas cooperativas_id_segmento_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativas
    ADD CONSTRAINT cooperativas_id_segmento_fkey FOREIGN KEY (id_segmento) REFERENCES public.segmentos(id_segmento);


--
-- TOC entry 3526 (class 2606 OID 35527)
-- Name: cooperativas cooperativas_provincia_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cooperativas
    ADD CONSTRAINT cooperativas_provincia_fk FOREIGN KEY (provincia_id) REFERENCES public.provincia(id);


--
-- TOC entry 3527 (class 2606 OID 35532)
-- Name: datos_facturacion datos_fact_canton_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.datos_facturacion
    ADD CONSTRAINT datos_fact_canton_fk FOREIGN KEY (canton_id) REFERENCES public.canton(id);


--
-- TOC entry 3528 (class 2606 OID 35537)
-- Name: datos_facturacion datos_fact_prov_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.datos_facturacion
    ADD CONSTRAINT datos_fact_prov_fk FOREIGN KEY (provincia_id) REFERENCES public.provincia(id);


--
-- TOC entry 3529 (class 2606 OID 35542)
-- Name: datos_facturacion datos_facturacion_id_cooperativa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.datos_facturacion
    ADD CONSTRAINT datos_facturacion_id_cooperativa_fkey FOREIGN KEY (id_cooperativa) REFERENCES public.cooperativas(id_cooperativa);


--
-- TOC entry 3530 (class 2606 OID 35547)
-- Name: equipos equipos_id_usuario_asignado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.equipos
    ADD CONSTRAINT equipos_id_usuario_asignado_fkey FOREIGN KEY (id_usuario_asignado) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 3531 (class 2606 OID 35552)
-- Name: incidencias_comercial incidencias_comercial_creado_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_comercial
    ADD CONSTRAINT incidencias_comercial_creado_por_fkey FOREIGN KEY (creado_por) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 3532 (class 2606 OID 35557)
-- Name: incidencias_comercial incidencias_comercial_id_cooperativa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_comercial
    ADD CONSTRAINT incidencias_comercial_id_cooperativa_fkey FOREIGN KEY (id_cooperativa) REFERENCES public.cooperativas(id_cooperativa) ON DELETE CASCADE;


--
-- TOC entry 3533 (class 2606 OID 35562)
-- Name: incidencias_comercial incidencias_comercial_id_ticket_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incidencias_comercial
    ADD CONSTRAINT incidencias_comercial_id_ticket_fkey FOREIGN KEY (id_ticket) REFERENCES public.tickets(id_ticket) ON DELETE SET NULL;


--
-- TOC entry 3534 (class 2606 OID 35567)
-- Name: info_contabilidad info_contabilidad_id_cooperativa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.info_contabilidad
    ADD CONSTRAINT info_contabilidad_id_cooperativa_fkey FOREIGN KEY (id_cooperativa) REFERENCES public.cooperativas(id_cooperativa) ON DELETE CASCADE;


--
-- TOC entry 3535 (class 2606 OID 35572)
-- Name: instalaciones instalaciones_id_contratacion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.instalaciones
    ADD CONSTRAINT instalaciones_id_contratacion_fkey FOREIGN KEY (id_contratacion) REFERENCES public.contrataciones(id_contratacion);


--
-- TOC entry 3536 (class 2606 OID 35577)
-- Name: instalaciones instalaciones_id_usuario_tecnico_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.instalaciones
    ADD CONSTRAINT instalaciones_id_usuario_tecnico_fkey FOREIGN KEY (id_usuario_tecnico) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 3537 (class 2606 OID 35582)
-- Name: listas_control listas_control_id_cooperativa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listas_control
    ADD CONSTRAINT listas_control_id_cooperativa_fkey FOREIGN KEY (id_cooperativa) REFERENCES public.cooperativas(id_cooperativa);


--
-- TOC entry 3538 (class 2606 OID 35587)
-- Name: pagos pagos_contrat_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_contrat_fk FOREIGN KEY (id_contratacion) REFERENCES public.contrataciones(id_contratacion) ON DELETE CASCADE NOT VALID;


--
-- TOC entry 3539 (class 2606 OID 35592)
-- Name: personal_cooperativa personal_cooperativa_id_cooperativa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_cooperativa
    ADD CONSTRAINT personal_cooperativa_id_cooperativa_fkey FOREIGN KEY (id_cooperativa) REFERENCES public.cooperativas(id_cooperativa);


--
-- TOC entry 3540 (class 2606 OID 35597)
-- Name: ticket_historial ticket_historial_id_ticket_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_historial
    ADD CONSTRAINT ticket_historial_id_ticket_fkey FOREIGN KEY (id_ticket) REFERENCES public.tickets(id_ticket);


--
-- TOC entry 3541 (class 2606 OID 35602)
-- Name: ticket_historial ticket_historial_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_historial
    ADD CONSTRAINT ticket_historial_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 3542 (class 2606 OID 35607)
-- Name: tickets tickets_id_categoria_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_id_categoria_fkey FOREIGN KEY (id_categoria) REFERENCES public.categorias(id_categoria);


--
-- TOC entry 3543 (class 2606 OID 35612)
-- Name: tickets tickets_id_equipo_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_id_equipo_fkey FOREIGN KEY (id_equipo) REFERENCES public.equipos(id_equipo);


--
-- TOC entry 3544 (class 2606 OID 35617)
-- Name: tickets tickets_id_tecnico_asignado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_id_tecnico_asignado_fkey FOREIGN KEY (id_tecnico_asignado) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 3545 (class 2606 OID 35622)
-- Name: tickets tickets_id_usuario_reporta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_id_usuario_reporta_fkey FOREIGN KEY (id_usuario_reporta) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 3546 (class 2606 OID 35627)
-- Name: usuario_categorias usuario_categorias_id_categoria_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario_categorias
    ADD CONSTRAINT usuario_categorias_id_categoria_fkey FOREIGN KEY (id_categoria) REFERENCES public.categorias(id_categoria);


--
-- TOC entry 3547 (class 2606 OID 35632)
-- Name: usuario_categorias usuario_categorias_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario_categorias
    ADD CONSTRAINT usuario_categorias_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 3548 (class 2606 OID 35637)
-- Name: usuarios usuarios_id_rol_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_id_rol_fkey FOREIGN KEY (id_rol) REFERENCES public.roles(id_rol);


-- Completed on 2025-09-28 23:19:27

--
-- PostgreSQL database dump complete
--

