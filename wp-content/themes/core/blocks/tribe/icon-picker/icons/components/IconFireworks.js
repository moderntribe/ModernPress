import * as React from 'react';
const SvgIconFireworks = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			fill="currentColor"
			d="m41.464 6.874-6.54-.95L32 0l-2.924 5.924-6.54.95 4.732 4.614L26.152 18 32 14.926 37.848 18l-1.116-6.512zM21.464 48.874l-6.54-.95L12 42l-2.924 5.924-6.54.95 4.732 4.614L6.152 60 12 56.926 17.848 60l-1.116-6.512zM61.464 48.874l-6.54-.95L52 42l-2.924 5.924-6.54.95 4.732 4.614L46.152 60 52 56.926 57.848 60l-1.116-6.512z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M18 32s14 6.666 14 30M32 22v14M46 32s-6.762 2.92-10.886 12.532M8 30a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM56 30a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"
		/>
	</svg>
);
export default SvgIconFireworks;
