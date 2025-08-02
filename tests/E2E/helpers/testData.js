import { faker } from '@faker-js/faker';
import axios from 'axios';

const API_BASE_URL = process.env.TEST_API_URL || 'http://localhost:8000/api';

export async function createTestData(config = {}) {
    const testData = {
        company: null,
        branches: [],
        staff: [],
        services: [],
        customers: [],
        appointments: [],
        users: []
    };

    // Create company if needed
    if (config.company) {
        testData.company = await createCompany(config.company);
    }

    // Create branches
    if (config.branches) {
        const branchCount = typeof config.branches === 'number' ? config.branches : 1;
        for (let i = 0; i < branchCount; i++) {
            const branch = await createBranch({
                name: `Branch ${i + 1}`,
                company_id: testData.company?.id
            });
            testData.branches.push(branch);
        }
    }

    // Create staff
    if (config.staff) {
        const staffCount = typeof config.staff === 'number' ? config.staff : 1;
        for (let i = 0; i < staffCount; i++) {
            const staff = await createStaff({
                name: faker.person.fullName(),
                email: faker.internet.email(),
                branch_id: testData.branches[0]?.id
            });
            testData.staff.push(staff);
        }
    }

    // Create services
    if (config.services) {
        const serviceCount = typeof config.services === 'number' ? config.services : 1;
        const serviceNames = ['Haircut', 'Massage', 'Facial', 'Manicure', 'Pedicure', 'Consultation'];
        
        for (let i = 0; i < serviceCount && i < serviceNames.length; i++) {
            const service = await createService({
                name: serviceNames[i],
                duration: [30, 45, 60, 90][Math.floor(Math.random() * 4)],
                price: faker.number.int({ min: 20, max: 200 })
            });
            testData.services.push(service);
        }
    }

    // Create customers
    if (config.customers) {
        const customerCount = typeof config.customers === 'number' ? config.customers : 1;
        for (let i = 0; i < customerCount; i++) {
            const customer = await createCustomer();
            testData.customers.push(customer);
        }
    }

    // Create appointments
    if (config.appointments && testData.customers.length > 0) {
        const appointmentCount = typeof config.appointments === 'number' ? config.appointments : 1;
        for (let i = 0; i < appointmentCount; i++) {
            const appointment = await createAppointment({
                customer_id: testData.customers[i % testData.customers.length].id,
                staff_id: testData.staff[i % testData.staff.length]?.id,
                service_id: testData.services[i % testData.services.length]?.id,
                branch_id: testData.branches[0]?.id
            });
            testData.appointments.push(appointment);
        }
    }

    return testData;
}

export async function cleanupTestData(testData) {
    if (!testData) return;

    // Delete in reverse order of dependencies
    const deletions = [];

    // Delete appointments
    if (testData.appointments?.length > 0) {
        deletions.push(...testData.appointments.map(a => deleteAppointment(a.id)));
    }

    // Delete customers
    if (testData.customers?.length > 0) {
        deletions.push(...testData.customers.map(c => deleteCustomer(c.id)));
    }

    // Delete services
    if (testData.services?.length > 0) {
        deletions.push(...testData.services.map(s => deleteService(s.id)));
    }

    // Delete staff
    if (testData.staff?.length > 0) {
        deletions.push(...testData.staff.map(s => deleteStaff(s.id)));
    }

    // Delete branches
    if (testData.branches?.length > 0) {
        deletions.push(...testData.branches.map(b => deleteBranch(b.id)));
    }

    // Delete users
    if (testData.users?.length > 0) {
        deletions.push(...testData.users.map(u => deleteUser(u.id)));
    }

    // Delete company
    if (testData.company) {
        deletions.push(deleteCompany(testData.company.id));
    }

    await Promise.all(deletions);
}

// Individual resource creators
export async function createCompany(data = {}) {
    const response = await axios.post(`${API_BASE_URL}/test/companies`, {
        name: data.name || faker.company.name(),
        email: data.email || faker.internet.email(),
        phone: data.phone || faker.phone.number(),
        ...data
    });
    return response.data.data;
}

export async function createBranch(data = {}) {
    const response = await axios.post(`${API_BASE_URL}/test/branches`, {
        name: data.name || faker.company.name() + ' Branch',
        address: data.address || faker.location.streetAddress(),
        city: data.city || faker.location.city(),
        postal_code: data.postal_code || faker.location.zipCode(),
        phone: data.phone || faker.phone.number(),
        ...data
    });
    return response.data.data;
}

export async function createStaff(data = {}) {
    const response = await axios.post(`${API_BASE_URL}/test/staff`, {
        first_name: data.first_name || faker.person.firstName(),
        last_name: data.last_name || faker.person.lastName(),
        email: data.email || faker.internet.email(),
        phone: data.phone || faker.phone.number(),
        ...data
    });
    return response.data.data;
}

export async function createService(data = {}) {
    const response = await axios.post(`${API_BASE_URL}/test/services`, {
        name: data.name || faker.commerce.productName(),
        duration: data.duration || 60,
        price: data.price || faker.number.int({ min: 20, max: 200 }),
        description: data.description || faker.commerce.productDescription(),
        ...data
    });
    return response.data.data;
}

export async function createCustomer(data = {}) {
    const response = await axios.post(`${API_BASE_URL}/test/customers`, {
        first_name: data.first_name || faker.person.firstName(),
        last_name: data.last_name || faker.person.lastName(),
        email: data.email || faker.internet.email(),
        phone: data.phone || faker.phone.number(),
        date_of_birth: data.date_of_birth || faker.date.birthdate({ min: 18, max: 80, mode: 'age' }),
        ...data
    });
    return response.data.data;
}

export async function createAppointment(data = {}) {
    const startDate = data.starts_at || faker.date.future({ days: 30 });
    const response = await axios.post(`${API_BASE_URL}/test/appointments`, {
        starts_at: startDate,
        ends_at: data.ends_at || new Date(startDate.getTime() + 60 * 60 * 1000),
        status: data.status || 'scheduled',
        ...data
    });
    return response.data.data;
}

export async function createUser(data = {}) {
    const response = await axios.post(`${API_BASE_URL}/test/users`, {
        name: data.name || faker.person.fullName(),
        email: data.email || faker.internet.email(),
        password: data.password || 'Test123!@#',
        role: data.role || 'staff',
        ...data
    });
    return response.data.data;
}

export async function createTestUser(data = {}) {
    return createUser(data);
}

// Delete functions
async function deleteAppointment(id) {
    await axios.delete(`${API_BASE_URL}/test/appointments/${id}`);
}

async function deleteCustomer(id) {
    await axios.delete(`${API_BASE_URL}/test/customers/${id}`);
}

async function deleteService(id) {
    await axios.delete(`${API_BASE_URL}/test/services/${id}`);
}

async function deleteStaff(id) {
    await axios.delete(`${API_BASE_URL}/test/staff/${id}`);
}

async function deleteBranch(id) {
    await axios.delete(`${API_BASE_URL}/test/branches/${id}`);
}

async function deleteUser(id) {
    await axios.delete(`${API_BASE_URL}/test/users/${id}`);
}

async function deleteCompany(id) {
    await axios.delete(`${API_BASE_URL}/test/companies/${id}`);
}

export async function cleanupTestUser(user) {
    if (user?.id) {
        await deleteUser(user.id);
    }
}

// Additional helper for creating complex scenarios
export const createTestScenario = {
    busyDay: async () => {
        const data = await createTestData({
            branches: 1,
            staff: 5,
            services: 10,
            customers: 50,
            appointments: 100
        });
        return data;
    },
    
    multiLocation: async () => {
        const data = await createTestData({
            branches: 3,
            staff: 15,
            services: 8,
            customers: 100
        });
        
        // Distribute staff across branches
        for (let i = 0; i < data.staff.length; i++) {
            await axios.patch(`${API_BASE_URL}/test/staff/${data.staff[i].id}`, {
                branch_id: data.branches[i % data.branches.length].id
            });
        }
        
        return data;
    },
    
    newBusiness: async () => {
        return createTestData({
            branches: 1,
            staff: 2,
            services: 5,
            customers: 0,
            appointments: 0
        });
    }
};

// Specialized creators
createTestData.appointment = createAppointment;
createTestData.customer = createCustomer;
createTestData.user = createUser;
createTestData.shift = async (data) => {
    const response = await axios.post(`${API_BASE_URL}/test/shifts`, data);
    return response.data.data;
};