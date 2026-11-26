#include<stdio.h>
int main()
{
    int i,j,space;
    for (i=1;i<=4;i++)
   
    
    {
        for(j=1;j<=i;j++)
       
        for(space=1;space<=j-4;space++)
        
        printf("*");
        printf(" ");
    printf("\n");

    }
    return 0;
}