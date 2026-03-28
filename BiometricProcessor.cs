using System;
using System.Collections.Generic;
using System.Linq;

namespace ALM.Biometrics.Core
{
    public class BiometricProcessor
    {
        private const int DescriptorSize = 128;

        public double CalculateEuclideanDistance(float[] descriptorA, float[] descriptorB)
        {
            if (descriptorA.Length != DescriptorSize || descriptorB.Length != DescriptorSize)
                throw new ArgumentException("Invalid descriptor size.");

            double sum = 0;
            for (int i = 0; i < DescriptorSize; i++)
            {
                double diff = descriptorA[i] - descriptorB[i];
                sum += diff * diff;
            }

            return Math.Sqrt(sum);
        }

        public bool IsMatch(float[] a, float[] b, double threshold = 0.6)
        {
            return CalculateEuclideanDistance(a, b) < threshold;
        }
    }
}
