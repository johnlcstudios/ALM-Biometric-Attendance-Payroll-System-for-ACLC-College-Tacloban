package com.alm.biometrics;

import java.util.List;
import java.util.ArrayList;

/**
 * EnrollmentService
 * Enterprise-grade service for managing employee biometric enrollment.
 */
public class EnrollmentService {
    
    private final List<EmployeeBiometric> registry;

    public EnrollmentService() {
        this.registry = new ArrayList<>();
    }

    public void enrollEmployee(String employeeId, float[] descriptor) {
        EmployeeBiometric bio = new EmployeeBiometric(employeeId, descriptor);
        registry.add(bio);
        System.out.println("Enrolled employee: " + employeeId);
    }

    private static class EmployeeBiometric {
        String id;
        float[] descriptor;

        EmployeeBiometric(String id, float[] descriptor) {
            this.id = id;
            this.descriptor = descriptor;
        }
    }
}
