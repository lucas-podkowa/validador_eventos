<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="200" alt="Laravel Logo"></a></p>

# Gestion de Eventos y Validador de Certificádos para la Facultad de Ingeniería

## Tecnología:
Laravel 11 + Livewire 3  + JetStream v5.x + MySQL

## Problema: 
La facultad de Ingeniería UNaM, a travez de la Secretaría de Extensión Universitaria, organiza e imparte distintos dicta actualmente 7 (siete) carreras de grado, donde gran parte de las asignaturas de sus tres primeros años son de **dictado común**, es decir, que asignaturas de distintas carreras que comparten sus contenidos mínimos, son impartidos como si fueran una sola asignatura común. El inconveniente surge al querer organizar los exámenes parciales y recuperatorios de las distintas carreras sin interrumpir o solapar el examen de otra carrera.

## Solución: 
Se desarrollo el presente sistema como un gestor de carreras, asignaturas y eventos relacionados a dichas asignaturas, permitiendo a los usuarios registrados, crear los eventos (exámenes) bajo ciertas restricciones puestas por el sistema, de manera que se puedan gestionar eficientemente esos tres primeros años a los que se le llama **Ciclo Básico**. Algunas de éstas restricciones son:
- No tener mas de 1 (un) examen de la misma carrera en un mismo turno (mañana o tarde)
- No tener mas de 2 (dos) exámenes de la misma carrera en el mismo día
- No tener mas de 1 (un) examen por ciclo (año) en el mismo turno (mañana o tarde) si corresponden a asignaturas de dictado común mas alla de que sean de carreras diferentes.
  
Tanto los usuarios registrados como los visitantes casuales del sitio podrán ver un **calendario actualizado de eventos** para ubicar rápidamente el cronograma de exámenes de todo el Ciclo Básico.
